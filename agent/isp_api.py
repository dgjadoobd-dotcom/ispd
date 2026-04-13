# ============================================================
# ISP API Client — talks to your Digital ISP ERP REST API
# ============================================================
import requests
import json
from datetime import datetime
from config import ISP_URL, ISP_USERNAME, ISP_PASSWORD

class IspApi:
    def __init__(self):
        self.base  = ISP_URL.rstrip('/')
        self.token = None

    # ── Auth ──────────────────────────────────────────────────
    def login(self) -> bool:
        try:
            r = requests.post(f"{self.base}/api/v1/auth/login", json={
                "username": ISP_USERNAME,
                "password": ISP_PASSWORD,
            }, timeout=10)
            data = r.json()
            if data.get('success'):
                self.token = data['token']
                print(f"✅ ISP API logged in — token expires: {data['expires']}")
                return True
            print(f"❌ ISP login failed: {data.get('error')}")
            return False
        except Exception as e:
            print(f"❌ ISP API connection error: {e}")
            return False

    def _headers(self):
        return {"Authorization": f"Bearer {self.token}"}

    # ── Dashboard ─────────────────────────────────────────────
    def get_stats(self) -> dict:
        r = requests.get(f"{self.base}/api/v1/dashboard/stats", headers=self._headers(), timeout=10)
        return r.json()

    # ── Customers ─────────────────────────────────────────────
    def search_customer(self, query: str) -> list:
        r = requests.get(f"{self.base}/api/v1/customers/search",
                         params={"q": query}, headers=self._headers(), timeout=10)
        return r.json().get('customers', [])

    def get_customer(self, customer_id: int) -> dict:
        r = requests.get(f"{self.base}/api/v1/customers/{customer_id}",
                         headers=self._headers(), timeout=10)
        return r.json().get('customer', {})

    def get_customer_invoices(self, customer_id: int) -> list:
        r = requests.get(f"{self.base}/api/v1/customers/{customer_id}/invoices",
                         headers=self._headers(), timeout=10)
        return r.json().get('invoices', [])

    # ── Payments ──────────────────────────────────────────────
    def record_payment(self, invoice_id: int, amount: float, method: str = "cash") -> dict:
        r = requests.post(f"{self.base}/api/v1/payments", json={
            "invoice_id":     invoice_id,
            "amount":         amount,
            "payment_method": method,
        }, headers=self._headers(), timeout=10)
        return r.json()

    def get_today_collections(self) -> dict:
        r = requests.get(f"{self.base}/api/v1/collections/today",
                         headers=self._headers(), timeout=10)
        return r.json()

    # ── Due customers (direct DB query via custom endpoint) ───
    def get_due_customers(self, days_ahead: int = 3) -> list:
        """Get customers whose bill is due within X days"""
        r = requests.get(f"{self.base}/api/v1/customers/search",
                         params={"q": ""}, headers=self._headers(), timeout=10)
        # Filter locally — due_amount > 0
        all_customers = r.json().get('customers', [])
        return [c for c in all_customers if float(c.get('due_amount', 0)) > 0]

    # ── Work Orders ───────────────────────────────────────────
    def get_workorders(self, status: str = "pending") -> list:
        r = requests.get(f"{self.base}/api/v1/workorders",
                         params={"status": status}, headers=self._headers(), timeout=10)
        return r.json().get('work_orders', [])

    # ── Trigger automation ────────────────────────────────────
    def run_billing_automation(self) -> bool:
        """Trigger the ISP billing cron via web"""
        try:
            r = requests.post(f"{self.base}/automation/run/billing",
                              headers=self._headers(), timeout=30)
            return r.status_code == 200
        except Exception as e:
            print(f"⚠ Billing automation error: {e}")
            return False
