import unittest
import time
import csv
import os
from typing import List, Dict, Any

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class TestMenuCalculation(unittest.TestCase):
    def setUp(self):
        """Launch Chrome and prepare screenshot directory."""
        base_dir = os.path.dirname(os.path.abspath(__file__))
        chrome_binary = os.path.join(base_dir, "chrome-win32", "chrome.exe")
        chromedriver_path = os.path.join(base_dir, "chromedriver-win32", "chromedriver.exe")

        options = Options()
        options.binary_location = chrome_binary
        # Keep UI visible for observation; comment next line to run headless
        # options.add_argument('--headless=new')

        # Stability flags
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-extensions')
        options.add_argument('--disable-popup-blocking')
        options.add_argument('--disable-notifications')
        options.add_argument('--disable-infobars')
        options.add_argument('--disable-blink-features=AutomationControlled')
        options.add_argument('--no-first-run')

        service = Service(chromedriver_path)
        self.driver = webdriver.Chrome(service=service, options=options)
        self.driver.maximize_window()

        # Screenshot dir under repo root
        self.screenshots_dir = os.path.join(os.path.dirname(base_dir), "screenshots")
        os.makedirs(self.screenshots_dir, exist_ok=True)

        # Fixed item order (matches IDs in menu.php)
        self.item_ids = [
            "cheesecake",
            "brownie",
            "macarons",
            "tiramisu",
            "sundae",
            "pannacotta",
            "fruittart",
            "eclair",
        ]

        # App URLs
        self.menu_url = "http://localhost/SWE30009-SOFTWARE-TESTING-AND-RELIABILITY/pages/menu.php"
        self.cart_url = "http://localhost/SWE30009-SOFTWARE-TESTING-AND-RELIABILITY/pages/cart.php"

        # Wait helper
        self.wait = WebDriverWait(self.driver, 8)

    def tearDown(self):
        """Close browser per test."""
        try:
            self.driver.close()
        except Exception:
            pass

    def _load_cases(self) -> List[Dict[str, Any]]:
        """
        Load CSV test cases.
        Supports two formats:
        1) Header: expected_total,discount,cheesecake,brownie,macarons,tiramisu,sundae,pannacotta,fruittart,eclair
        2) Raw rows: expected_total, qty1..qty8 [, discount]
        """
        csv_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_cases.csv")
        if not os.path.exists(csv_path):
            self.skipTest(f"Missing CSV file: {csv_path}")

        with open(csv_path, newline='', encoding='utf-8') as f:
            reader = csv.reader(f)
            rows = list(reader)

        if not rows:
            self.skipTest("CSV file is empty")

        header = rows[0]
        cases: List[Dict[str, Any]] = []

        def parse_expected(val: str) -> float:
            # Accept "RM 12.34" or "12.34"
            s = val.strip()
            if s.upper().startswith("RM"):
                s = s[2:].strip()
            # Allow commas in thousands
            s = s.replace(",", "")
            try:
                return round(float(s), 2)
            except Exception:
                raise ValueError(f"Invalid expected total: {val}")

        # Header-based parsing
        if header and ("expected_total" in [h.strip().lower() for h in header] or "cheesecake" in [h.strip().lower() for h in header]):
            col_map = {h.strip().lower(): i for i, h in enumerate(header)}
            for i, row in enumerate(rows[1:], start=2):
                if not row or all((c or "").strip() == "" for c in row):
                    continue
                try:
                    expected = parse_expected(row[col_map.get("expected_total", 0)])
                except Exception as e:
                    print(f"Skipping row {i}: {e}")
                    continue

                discount = 0
                if "discount" in col_map:
                    try:
                        discount = int((row[col_map["discount"]] or "0").strip())
                    except Exception:
                        discount = 0

                qty_map: Dict[str, int] = {}
                for item_id in self.item_ids:
                    idx = col_map.get(item_id)
                    val = row[idx] if idx is not None and idx < len(row) else "0"
                    try:
                        qty_map[item_id] = int((val or "0").strip())
                    except Exception:
                        qty_map[item_id] = 0

                cases.append({"expected_total": expected, "discount": discount, "quantities": qty_map})
        else:
            # Raw rows parsing: expected, qty1..qty8 [, discount]
            for i, row in enumerate(rows, start=1):
                if len(row) < 9:
                    print(f"Skipping malformed row {i}: {row}")
                    continue
                try:
                    expected = parse_expected(row[0])
                except Exception as e:
                    print(f"Skipping row {i}: {e}")
                    continue

                qtys = []
                for col in row[1:9]:
                    val = (col or "0").strip()
                    try:
                        qtys.append(int(val))
                    except Exception:
                        qtys.append(0)
                quantities = dict(zip(self.item_ids, qtys))

                discount = 0
                if len(row) >= 10:
                    try:
                        discount = int((row[9] or "0").strip())
                    except Exception:
                        discount = 0

                cases.append({"expected_total": expected, "discount": discount, "quantities": quantities})

        return cases

    def _fill_menu_and_calculate(self, quantities: Dict[str, int], discount: int) -> None:
        """Fill menu quantities, set discount, click Calculate Bill."""
        self.driver.get(self.menu_url)

        # Fill quantities by id="qty_<item_id>"
        for item_id, qty in quantities.items():
            input_id = f"qty_{item_id}"
            el = self.wait.until(EC.presence_of_element_located((By.ID, input_id)))
            try:
                el.clear()
            except Exception:
                pass
            if qty is not None:
                el.send_keys(str(qty))
            time.sleep(0.05)

        # Apply discount if requested
        try:
            toggle = self.driver.find_element(By.ID, "discountToggle")
            if discount and not toggle.is_selected():
                toggle.click()
            if not discount and toggle.is_selected():
                toggle.click()
        except Exception:
            # If toggle not found, ignore
            pass

        # Click Calculate Bill
        btn = self.wait.until(EC.element_to_be_clickable((By.ID, "goToCart")))
        btn.click()

        # Wait for cart page to load by presence of summary total
        self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".summary-total-amount")))

    def _read_cart_total(self) -> float:
        """Read 'Total Payment' from cart.php and normalize to float."""
        total_el = self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".summary-total-amount")))
        text = total_el.text.strip()  # e.g., "RM 12.34"
        # Normalize to float
        s = text
        if s.upper().startswith("RM"):
            s = s[2:].strip()
        s = s.replace(",", "")
        try:
            return round(float(s), 2)
        except Exception:
            raise ValueError(f"Could not parse cart total from '{text}'")

    def test_menu_calculation_all_cases_from_csv(self):
        """Run all test cases from CSV: fill menu, calculate, compare, report."""
        cases = self._load_cases()
        failures = []

        for idx, case in enumerate(cases, start=1):
            expected = float(case["expected_total"])
            quantities = case["quantities"]
            discount = int(case.get("discount", 0))

            # Navigate/fill/calculate
            self._fill_menu_and_calculate(quantities, discount)
            time.sleep(0.2)

            # Read total and compare
            try:
                actual = self._read_cart_total()
            except Exception as e:
                actual = float('nan')
                print(f"Case {idx}: ERROR reading total: {e}")

            # Assertion with 2-decimal precision
            try:
                self.assertAlmostEqual(actual, expected, places=2)
                result = "PASS"
                print(f"Test case {idx}: PASS  expected={expected:.2f}, actual={actual:.2f}")
                screenshot_name = f"case{idx}_PASS.png"
            except AssertionError:
                result = "FAIL"
                print(f"Test case {idx}: FAIL  expected={expected:.2f}, actual={actual:.2f}")
                screenshot_name = f"case{idx}_FAIL.png"
                failures.append((idx, expected, actual))

            # Save screenshot
            screenshot_path = os.path.join(self.screenshots_dir, screenshot_name)
            try:
                self.driver.save_screenshot(screenshot_path)
                print(f"Screenshot saved: {screenshot_path}\n")
            except Exception as e:
                print(f"Could not save screenshot for case {idx}: {e}")

            # Small pause between cases
            time.sleep(0.2)

        # Summary
        if failures:
            print("\nSummary of failed cases:")
            for fidx, exp, act in failures:
                print(f" - case {fidx}: expected={exp:.2f}, actual={act:.2f}")
            self.fail(f"{len(failures)} test case(s) failed")
        else:
            print("\nAll test cases PASSED")


if __name__ == "__main__":
    unittest.main()

