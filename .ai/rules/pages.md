---
paths:
  - 'app/**,resources/views/pages/**,routes/**'
---

# Pages

## Print shops are tenant boundaries
PrintShop is a tenant in a shared database. Owners resolve their own shop; staff require staff_shop_id. Never fall back to PrintShop::first(). Customer ordering uses an explicit locked selected shop and rechecks approval, active service, and unexpired service pauses. Operational lookups and private files must enforce shop/customer ownership. Public registration cannot choose arbitrary roles.
