import unittest
import time
import csv
import os
import math
import base64
from typing import List, Dict, Any
from decimal import Decimal, ROUND_HALF_UP

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

        # Wait helpers
        self.wait = WebDriverWait(self.driver, 8)
        self.wait_cart = WebDriverWait(self.driver, 5)
        # Custom viewport for cart PASS/FAIL screenshots
        self.result_viewport = {"width": 1600, "height": 800, "scroll_y": 90}

    def tearDown(self):
        """Close browser per test."""
        try:
            self.driver.close()
        except Exception:
            pass

    def _load_cases(self) -> List[Dict[str, Any]]:
        """
        Load CSV test cases in the format:
        expected_total, discount(bool), qty1..qty8, testing_name
        e.g.:
        41.66, false, 1, 0, 1, 0, 0, 1, 0, 1, Normal testing
        """
        csv_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_cases.csv")
        if not os.path.exists(csv_path):
            self.skipTest(f"Missing CSV file: {csv_path}")

        with open(csv_path, newline='', encoding='utf-8') as f:
            reader = csv.reader(f)
            rows = list(reader)

        if not rows:
            self.skipTest("CSV file is empty")

        cases: List[Dict[str, Any]] = []

        def parse_expected(val: str) -> Decimal:
            s = (val or "").strip()
            if s.upper().startswith("RM"):
                s = s[2:].strip()
            s = s.replace(",", "")
            return Decimal(s).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

        def parse_discount(val: str) -> int:
            s = (val or "").strip().lower()
            if s in {"true", "1", "yes", "on"}:
                return 1
            return 0

        for i, row in enumerate(rows, start=1):
            # Normalize and trim cells
            cells = [ (c or "").strip() for c in row ]
            if not cells or all(c == "" for c in cells):
                continue

            if len(cells) < 11:
                print(f"Skipping malformed row {i}: expected 11 columns, got {len(cells)} -> {cells}")
                continue

            try:
                expected = parse_expected(cells[0])
            except Exception as e:
                print(f"Skipping row {i}: {e}")
                continue

            discount = parse_discount(cells[1])

            qty_tokens = cells[2:10]  # exactly 8 quantities
            if len(qty_tokens) != 8:
                print(f"Skipping malformed row {i}: need 8 quantities, got {len(qty_tokens)}")
                continue

            qtys: List[int] = []
            for q in qty_tokens:
                # Handle stray spaces/commas like "3 ,3" gracefully
                q = q.replace(" ", "")
                try:
                    qtys.append(int(q))
                except Exception:
                    qtys.append(0)

            quantities = dict(zip(self.item_ids, qtys))

            test_name = cells[10] or f"Case {i}"

            cases.append({
                "name": test_name,
                "expected_total": expected,
                "discount": discount,
                "quantities": quantities
            })

        return cases

    def _fill_menu_and_calculate(self, quantities: Dict[str, int], discount: int, case_idx: int) -> None:
        """Fill menu quantities, set discount, then navigate to cart."""
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
            time.sleep(1)  # wait 1 second per item input

        # Ensure discount toggle matches requested state
        self._set_discount_toggle(bool(discount))

        # Wait 3 seconds before clicking Calculate Bill
        btn = self.wait.until(EC.element_to_be_clickable((By.ID, "goToCart")))
        time.sleep(3)
        btn.click()

    def _read_cart_total(self) -> Decimal:
        """Read 'Total Payment' from cart.php and normalize to Decimal(2dp)."""
        total_el = self.wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".summary-total-amount")))
        text = total_el.text.strip()  # e.g., "RM 12.34"
        s = text
        if s.upper().startswith("RM"):
            s = s[2:].strip()
        s = s.replace(",", "")
        try:
            return Decimal(s).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)
        except Exception:
            raise ValueError(f"Could not parse cart total from '{text}'")

    def _screenshot_custom(self, path: str, width: int, height: int, scroll_y: int = 0) -> None:
        try:
            self.driver.execute_cdp_cmd("Emulation.setDeviceMetricsOverride", {
                "mobile": False,
                "deviceScaleFactor": 1,
                "width": int(width),
                "height": int(height),
            })
            if scroll_y and scroll_y > 0:
                self.driver.execute_script(f"window.scrollTo(0, {int(scroll_y)})")
            shot = self.driver.execute_cdp_cmd("Page.captureScreenshot", {
                "format": "png",
                "fromSurface": True
            })
            import base64
            with open(path, "wb") as f:
                f.write(base64.b64decode(shot["data"]))
        finally:
            try:
                self.driver.execute_cdp_cmd("Emulation.clearDeviceMetricsOverride", {})
            except Exception:
                pass

    def _screenshot_full_page(self, path: str) -> None:
        """Capture a full-page PNG screenshot with exact page width/height."""
        try:
            metrics = self.driver.execute_cdp_cmd("Page.getLayoutMetrics", {})
            content = metrics.get("contentSize", {})
            width = math.ceil(content.get("width", 1280))
            height = math.ceil(content.get("height", 800))

            # Match viewport to full page size
            self.driver.execute_cdp_cmd("Emulation.setDeviceMetricsOverride", {
                "mobile": False,
                "deviceScaleFactor": 1,
                "width": width,
                "height": height,
            })

            shot = self.driver.execute_cdp_cmd("Page.captureScreenshot", {
                "format": "png",
                "fromSurface": True
            })
            with open(path, "wb") as f:
                f.write(base64.b64decode(shot["data"]))
        finally:
            # Restore metrics
            try:
                self.driver.execute_cdp_cmd("Emulation.clearDeviceMetricsOverride", {})
            except Exception:
                pass

    def _set_discount_toggle(self, on: bool) -> None:
        """Ensure the discount toggle is set to the desired state (on/off)."""
        try:
            toggle = self.wait.until(EC.presence_of_element_located((By.ID, "discountToggle")))
            # Bring into view to avoid intercept errors
            self.driver.execute_script("arguments[0].scrollIntoView({block:'center'});", toggle)
            time.sleep(0.2)

            def is_on(elem) -> bool:
                # Primary: checkbox-like controls
                try:
                    return bool(elem.is_selected())
                except Exception:
                    pass
                # ARIA/button-like toggles
                aria_pressed = (elem.get_attribute("aria-pressed") or "").lower()
                aria_checked = (elem.get_attribute("aria-checked") or "").lower()
                data_state = (elem.get_attribute("data-state") or "").lower()
                classes = (elem.get_attribute("class") or "").lower()
                if aria_pressed in ("true", "1"):
                    return True
                if aria_checked in ("true", "1"):
                    return True
                if data_state in ("on", "true", "1"):
                    return True
                return any(k in classes for k in ["active", "on", "checked"])

            current = is_on(toggle)
            desired = bool(on)

            if current != desired:
                # Try native click first
                try:
                    self.wait.until(EC.element_to_be_clickable((By.ID, "discountToggle")))
                    toggle.click()
                except Exception:
                    # Fallback: JS click
                    self.driver.execute_script("arguments[0].click();", toggle)
                time.sleep(0.3)

                # Verify, click again if needed
                if is_on(toggle) != desired:
                    try:
                        toggle.click()
                    except Exception:
                        self.driver.execute_script("arguments[0].click();", toggle)
                    time.sleep(0.3)
        except Exception:
            # If toggle is not present, skip silently
            pass

    def test_menu_calculation_all_cases_from_csv(self):
        """Run all test cases from CSV: fill menu, calculate, compare, report."""
        cases = self._load_cases()
        failures = []
        epsilon = Decimal("0.05")

        for idx, case in enumerate(cases, start=1):
            expected: Decimal = case["expected_total"]
            quantities = case["quantities"]
            discount = int(case.get("discount", 0))
            test_name = case.get("name", f"Case {idx}")

            # Navigate/fill/calculate (menu screenshot removed; no viewport passed)
            self._fill_menu_and_calculate(
                quantities,
                discount,
                case_idx=idx,
            )
            time.sleep(0.2)

            # Read total and compare
            try:
                actual: Decimal = self._read_cart_total()
            except Exception as e:
                print(f"[Case {idx}] ERROR reading total: {e}")
                actual = Decimal("NaN")

            diff: Decimal = (actual - expected).copy_abs()
            is_pass = diff <= epsilon
            result = "PASS" if is_pass else "FAIL"
            screenshot_name = f"case{idx}_{result}.png"
            if not is_pass:
                failures.append((idx, test_name, expected, actual, diff))

            # Per-case console block with name
            banner = "=" * 120
            print(banner)
            print(f"Test Case {idx} — {test_name}: {result}")
            print("-" * 120)
            print(f"Expected Total : RM {format(expected, '.2f')}")
            print(f"Actual Total   : RM {format(actual, '.2f')}")
            print(f"Difference     : RM {format(diff, '.2f')} (tolerance RM {format(epsilon, '.2f')})")
            print(f"Discount       : {'ON (50%)' if discount else 'OFF'}")
            qty_str = ", ".join([f"{k}:{v}" for k, v in quantities.items()])
            print(f"Quantities     : {qty_str}")
            print("-" * 120)

            # Custom-sized screenshot (cart page) for PASS/FAIL
            screenshot_path = os.path.join(self.screenshots_dir, screenshot_name)
            try:
                rv = getattr(self, "result_viewport", {"width": 1280, "height": 720, "scroll_y": 0})
                self._screenshot_custom(
                    screenshot_path,
                    width=int(rv.get("width", 1280)),
                    height=int(rv.get("height", 720)),
                    scroll_y=int(rv.get("scroll_y", 0)),
                )
                print(f"Screenshot saved: {screenshot_path}")
            except Exception as e:
                print(f"Could not save screenshot for case {idx}: {e}")
            print(banner + "\n")

            time.sleep(0.2)

        # Summary of test outcomes
        if failures:
            print("\n" + "=" * 120)
            print(f"Completed with {len(cases)-len(failures)} passed case(s) and {len(failures)} failed case(s).")
            for fidx, fname, exp, act, d in failures:
                print("-" * 120)
                print("--- Failed Test Case ---")
                print(f"Case {fidx} — {fname}")
                print(f"Expected Total : RM {format(exp, '.2f')}")
                print(f"Actual Total   : RM {format(act, '.2f')}")
                print(f"Difference     : RM {format(d, '.2f')}")
            print("=" * 120 + "\n")
        else:
            print("\n" + "=" * 120)
            print("All test cases PASSED")
            print("=" * 120)

if __name__ == "__main__":
    unittest.main()
