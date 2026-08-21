import { pickByIncludes, withAllPermissions } from "@/config/menu-config";

export const hasPermission = (
  userPermissions: string[],
  requiredPermission: string,
): boolean => {
  if (!userPermissions || !requiredPermission) return false;

  return userPermissions?.some((permission) => {
    if (permission.endsWith(".*")) {
      const moduleName = permission.split(".")[0];
      return requiredPermission.startsWith(moduleName);
    }
    return permission === requiredPermission;
  });
};

// Quyền cho module Land
export const LAND_PERMISSIONS = {
  CREATE: "land.create",
  UPDATE_OWN: "land.update.own",
  UPDATE_ALL: "land.update.all",
  DELETE_OWN: "land.delete.own",
  DELETE_ALL: "land.delete.all",
  VIEW_OWN: "land.view.own",
  VIEW_ALL: "land.view.all",
  FULL: "land.*",
} as const;

// Quyền cho module User
export const USER_PERMISSIONS = {
  CREATE: "user.create",
  UPDATE: "user.update",
  DELETE: "user.delete",
  VIEW: "user.view",
  APPROVE: "user.approve",
  PERMISSION_LIST: "user.permission.list",
  PERMISSION_UPDATE: "user.permission.update",
  FULL: "user.*",
} as const;

// Quyền cho module Worker
export const WORKER_PERMISSIONS = {
  CREATE: "worker.create",
  UPDATE_OWN: "worker.update.own",
  DELETE_OWN: "worker.delete.own",
  VIEW_OWN: "worker.view.own",
  VIEW_ALL: "worker.view.all",
  FULL: "worker.*",
} as const;

// Quyền cho module Plant
export const PLANT_PERMISSIONS = {
  CREATE: "plant.create",
  UPDATE_OWN: "plant.update.own",
  UPDATE_ALL: "plant.update.all",
  DELETE_OWN: "plant.delete.own",
  DELETE_ALL: "plant.delete.all",
  VIEW_OWN: "plant.view.own",
  VIEW_ALL: "plant.view.all",
  FULL: "plant.*",
} as const;

// Quyền cho module Harvest
export const HARVEST_PERMISSIONS = {
  PLAN_CREATE: "harvest_plan.create",
  PLAN_VIEW_SELF: "harvest_plan.view.self",
  PLAN_VIEW_OWN: "harvest_plan.view.own",
  PLAN_VIEW_ALL: "harvest_plan.view.all",
  PLAN_UPDATE_SELF: "harvest_plan.update.self",
  PLAN_UPDATE_OWN: "harvest_plan.update.own",
  PLAN_UPDATE_ALL: "harvest_plan.update.all",
  PLAN_DELETE_SELF: "harvest_plan.delete.self",
  PLAN_DELETE_OWN: "harvest_plan.delete.own",
  PLAN_DELETE_ALL: "harvest_plan.delete.all",

  SCHEDULE_CREATE: "harvest_schedule.create",
  SCHEDULE_VIEW_SELF: "harvest_schedule.view.self",
  SCHEDULE_VIEW_OWN: "harvest_schedule.view.own",
  SCHEDULE_VIEW_ALL: "harvest_schedule.view.all",
  SCHEDULE_UPDATE_SELF: "harvest_schedule.update.self",
  SCHEDULE_UPDATE_OWN: "harvest_schedule.update.own",
  SCHEDULE_UPDATE_ALL: "harvest_schedule.update.all",
  SCHEDULE_DELETE_SELF: "harvest_schedule.delete.self",
  SCHEDULE_DELETE_OWN: "harvest_schedule.delete.own",
  SCHEDULE_DELETE_ALL: "harvest_schedule.delete.all",

  RESULT_VIEW_SELF: "harvest_result.view.self",
  RESULT_VIEW_OWN: "harvest_result.view.own",
  RESULT_VIEW_ALL: "harvest_result.view.all",

  RESULT_UPDATE_SELF: "harvest_result.update.self",
  RESULT_UPDATE_OWN: "harvest_result.update.own",
  RESULT_UPDATE_ALL: "harvest_result.update.all",

  PLAN_FULL: "harvest_plan.*",
  SCHEDULE_FULL: "harvest_schedule.*",
  RESULT_FULL: "harvest_result.*",
} as const;

export const TRANSACTION_TICKET_PERMISSIONS = {
  PURCHASE_VIEW_SELF: "transaction_ticket.purchase.view.self",
  PURCHASE_VIEW_OWN: "transaction_ticket.purchase.view.own",
  PURCHASE_VIEW_ALL: "transaction_ticket.purchase.view.all",

  PURCHASE_CREATE: "transaction_ticket.purchase.create",

  PURCHASE_UPDATE_SELF: "transaction_ticket.purchase.update.self",
  PURCHASE_UPDATE_OWN: "transaction_ticket.purchase.update.own",
  PURCHASE_UPDATE_ALL: "transaction_ticket.purchase.update.all",

  PURCHASE_DELETE_SELF: "transaction_ticket.purchase.delete.self",
  PURCHASE_DELETE_OWN: "transaction_ticket.purchase.delete.own",
  PURCHASE_DELETE_ALL: "transaction_ticket.purchase.delete.all",

  PURCHASE_FULL: "transaction_ticket.purchase.*",

  SALE_VIEW_SELF: "transaction_ticket.sale.view.self",
  SALE_VIEW_OWN: "transaction_ticket.sale.view.own",
  SALE_VIEW_ALL: "transaction_ticket.sale.view.all",

  SALE_CREATE: "transaction_ticket.sale.create",

  SALE_UPDATE_SELF: "transaction_ticket.sale.update.self",
  SALE_UPDATE_OWN: "transaction_ticket.sale.update.own",
  SALE_UPDATE_ALL: "transaction_ticket.sale.update.all",

  SALE_DELETE_SELF: "transaction_ticket.sale.delete.self",
  SALE_DELETE_OWN: "transaction_ticket.sale.delete.own",
  SALE_DELETE_ALL: "transaction_ticket.sale.delete.all",

  SALE_FULL: "transaction_ticket.sale.*",
} as const;

export const COMPANY_PERMISSIONS = {
  CREATE: "company.create",
  VIEW_ALL: "company.view.all",
  UPDATE_ALL: "company.update.all",
  DELETE_ALL: "company.delete.all",
  FULL: "company.*",
} as const;

export const COMPANY_GROUP_PERMISSIONS = {
  CREATE: "company_group.create",

  VIEW_OWN: "company_group.view.own",
  VIEW_ALL: "company_group.view.all",

  UPDATE_OWN: "company_group.update.own",
  UPDATE_ALL: "company_group.update.all",

  DELETE_OWN: "company_group.delete.own",
  DELETE_ALL: "company_group.delete.all",

  FULL: "company_group.*",
} as const;

export const COMPANY_MEMBER_PERMISSIONS = {
  CREATE: "company_member.create",

  VIEW_SELF: "company_member.view.self",
  VIEW_OWN: "company_member.view.own",
  VIEW_ALL: "company_member.view.all",

  UPDATE_SELF: "company_member.update.self",
  UPDATE_OWN: "company_member.update.own",
  UPDATE_ALL: "company_member.update.all",

  DELETE_SELF: "company_member.delete.self",
  DELETE_OWN: "company_member.delete.own",
  DELETE_ALL: "company_member.delete.all",

  FULL: "company_member.*",
} as const;

export const CUSTOM_FIELD_PERMISSIONS = {
  CREATE: "custom_field.create",

  VIEW_SELF: "custom_field.view.self",
  VIEW_OWN: "custom_field.view.own",
  VIEW_ALL: "custom_field.view.all",

  UPDATE_SELF: "custom_field.update.self",
  UPDATE_OWN: "custom_field.update.own",
  UPDATE_ALL: "custom_field.update.all",

  DELETE_SELF: "custom_field.delete.self",
  DELETE_OWN: "custom_field.delete.own",
  DELETE_ALL: "custom_field.delete.all",

  FULL: "custom_field.*",
} as const;

export const FACTORY_PERMISSIONS = {
  CREATE: "factory.create",

  VIEW_OWN: "factory.view.own",
  VIEW_ALL: "factory.view.all",

  UPDATE_OWN: "factory.update.own",
  UPDATE_ALL: "factory.update.all",

  DELETE_OWN: "factory.delete.own",
  DELETE_ALL: "factory.delete.all",

  FULL: "factory.*",
} as const;

export const FINISHED_GOODS_RECEIPT_PERMISSIONS = {
  CREATE: "finished_goods_receipt.create",

  VIEW_OWN: "finished_goods_receipt.view.own",
  VIEW_ALL: "finished_goods_receipt.view.all",

  UPDATE_OWN: "finished_goods_receipt.update.own",
  UPDATE_ALL: "finished_goods_receipt.update.all",

  DELETE_OWN: "finished_goods_receipt.delete.own",
  DELETE_ALL: "finished_goods_receipt.delete.all",

  FULL: "finished_goods_receipt.*",
} as const;

export const PRODUCTION_ORDER_PERMISSIONS = {
  CREATE: "production_order.create",

  VIEW_OWN: "production_order.view.own",
  VIEW_ALL: "production_order.view.all",

  UPDATE_OWN: "production_order.update.own",
  UPDATE_ALL: "production_order.update.all",

  DELETE_OWN: "production_order.delete.own",
  DELETE_ALL: "production_order.delete.all",

  FULL: "production_order.*",
} as const;

export const RAW_MATERIAL_RELEASE_PERMISSIONS = {
  CREATE: "raw_material_release.create",

  VIEW_OWN: "raw_material_release.view.own",
  VIEW_ALL: "raw_material_release.view.all",

  UPDATE_OWN: "raw_material_release.update.own",
  UPDATE_ALL: "raw_material_release.update.all",

  DELETE_OWN: "raw_material_release.delete.own",
  DELETE_ALL: "raw_material_release.delete.all",

  FULL: "raw_material_release.*",
} as const;

export const RAW_MATERIAL_TANK_PERMISSIONS = {
  CREATE: "raw_material_tank.create",

  VIEW_OWN: "raw_material_tank.view.own",
  VIEW_ALL: "raw_material_tank.view.all",

  UPDATE_OWN: "raw_material_tank.update.own",
  UPDATE_ALL: "raw_material_tank.update.all",

  DELETE_OWN: "raw_material_tank.delete.own",
  DELETE_ALL: "raw_material_tank.delete.all",

  FULL: "raw_material_tank.*",
} as const;

export const PRODUCT_TANK_PERMISSIONS = {
  CREATE: "product_tank.create",

  VIEW_OWN: "product_tank.view.own",
  VIEW_ALL: "product_tank.view.all",

  UPDATE_OWN: "product_tank.update.own",
  UPDATE_ALL: "product_tank.update.all",

  DELETE_OWN: "product_tank.delete.own",
  DELETE_ALL: "product_tank.delete.all",

  FULL: "product_tank.*",
} as const;

export const PRODUCT_TYPE_PERMISSIONS = {
  CREATE: "product_type.create",

  VIEW_OWN: "product_type.view.own",
  VIEW_ALL: "product_type.view.all",

  UPDATE_OWN: "product_type.update.own",
  UPDATE_ALL: "product_type.update.all",

  DELETE_OWN: "product_type.delete.own",
  DELETE_ALL: "product_type.delete.all",

  FULL: "product_type.*",
} as const;

export const TRANSPORTATION_ROUTE_PERMISSIONS = {
  CREATE: "transportation_route.create",

  VIEW_OWN: "transportation_route.view.own",
  VIEW_ALL: "transportation_route.view.all",

  UPDATE_OWN: "transportation_route.update.own",
  UPDATE_ALL: "transportation_route.update.all",

  DELETE_OWN: "transportation_route.delete.own",
  DELETE_ALL: "transportation_route.delete.all",

  FULL: "transportation_route.*",
} as const;

export const VEHICLE_PERMISSIONS = {
  CREATE: "vehicle.create",

  VIEW_SELF: "vehicle.view.self",
  VIEW_OWN: "vehicle.view.own",
  VIEW_ALL: "vehicle.view.all",

  UPDATE_SELF: "vehicle.update.self",
  UPDATE_OWN: "vehicle.update.own",
  UPDATE_ALL: "vehicle.update.all",

  DELETE_SELF: "vehicle.delete.self",
  DELETE_OWN: "vehicle.delete.own",
  DELETE_ALL: "vehicle.delete.all",

  FULL: "vehicle.*",
} as const;

export const SALES_CUSTOMER_PERMISSIONS = {
  CREATE: "sales_customer.create",

  VIEW_SELF: "sales_customer.view.self",
  VIEW_OWN: "sales_customer.view.own",
  VIEW_ALL: "sales_customer.view.all",

  UPDATE_SELF: "sales_customer.update.self",
  UPDATE_OWN: "sales_customer.update.own",
  UPDATE_ALL: "sales_customer.update.all",

  DELETE_SELF: "sales_customer.delete.self",
  DELETE_OWN: "sales_customer.delete.own",
  DELETE_ALL: "sales_customer.delete.all",

  FULL: "sales_customer.*",
} as const;

export const SALES_ORDER_PERMISSIONS = {
  CREATE: "sales_order.create",

  VIEW_SELF: "sales_order.view.self",
  VIEW_OWN: "sales_order.view.own",
  VIEW_ALL: "sales_order.view.all",

  UPDATE_SELF: "sales_order.update.self",
  UPDATE_OWN: "sales_order.update.own",
  UPDATE_ALL: "sales_order.update.all",

  DELETE_SELF: "sales_order.delete.self",
  DELETE_OWN: "sales_order.delete.own",
  DELETE_ALL: "sales_order.delete.all",

  FULL: "sales_order.*",
} as const;

export const SALES_ISSUE_PERMISSIONS = {
  CREATE: "sales_issue.create",

  VIEW_SELF: "sales_issue.view.self",
  VIEW_OWN: "sales_issue.view.own",
  VIEW_ALL: "sales_issue.view.all",

  UPDATE_SELF: "sales_issue.update.self",
  UPDATE_OWN: "sales_issue.update.own",
  UPDATE_ALL: "sales_issue.update.all",

  DELETE_SELF: "sales_issue.delete.self",
  DELETE_OWN: "sales_issue.delete.own",
  DELETE_ALL: "sales_issue.delete.all",

  ISSUE_SELF: "sales_issue.issue.self",
  ISSUE_OWN: "sales_issue.approve.own",
  ISSUE_ALL: "sales_issue.issue.all",

  CANCEL_SELF: "sales_issue.cancel.self",
  CANCEL_OWN: "sales_issue.cancel.own",
  CANCEL_ALL: "sales_issue.cancel.all",

  FULL: "sales_issue.*",
} as const;

export const PALLET_PERMISSIONS = {
  CREATE: "pallet.create",

  VIEW_SELF: "pallet.view.self",
  VIEW_OWN: "pallet.view.own",
  VIEW_ALL: "pallet.view.all",

  UPDATE_SELF: "pallet.update.self",
  UPDATE_OWN: "pallet.update.own",
  UPDATE_ALL: "pallet.update.all",

  DELETE_SELF: "pallet.delete.self",
  DELETE_OWN: "pallet.delete.own",
  DELETE_ALL: "pallet.delete.all",

  CANCEL_SELF: "pallet.cancel.self",
  CANCEL_OWN: "pallet.cancel.own",
  CANCEL_ALL: "pallet.cancel.all",

  PACK_SELF: "pallet.pack.self",
  PACK_OWN: "pallet.pack.own",
  PACK_ALL: "pallet.pack.all",

  SHIP_SELF: "pallet.ship.self",
  SHIP_OWN: "pallet.ship.own",
  SHIP_ALL: "pallet.ship.all",

  FULL: "pallet.*",
} as const;

export const PRICE_PERMISSIONS = {
  CREATE: "price.create",

  // VIEW_SELF: "price.view.self",
  VIEW_OWN: "price.view.own",
  // VIEW_ALL: "price.view.all",

  // UPDATE_SELF: "price.update.self",
  UPDATE_OWN: "price.update.own",
  // UPDATE_ALL: "price.update.all",

  // DELETE_SELF: "price.delete.self",
  DELETE_OWN: "price.delete.own",
  // DELETE_ALL: "price.delete.all",

  FULL: "price.*",
} as const;

export const INSPECTOR_PERMISSIONS = {
  CREATE: "land.support.create",
  VIEW_OWN: "land.support.view.own",
  UPDATE_OWN: "land.support.update.own",
  DELETE_OWN: "land.support.delete.own",
} as const;

export const routePermissions: Record<string, string[]> = {
  "/land/land-list": withAllPermissions(LAND_PERMISSIONS),
  "/land/map": withAllPermissions(LAND_PERMISSIONS),
  "/land/plants": withAllPermissions(PLANT_PERMISSIONS),

  "/farmer/farmer-list": withAllPermissions(USER_PERMISSIONS),

  "/farmer/harvest-plan": pickByIncludes(HARVEST_PERMISSIONS, [".plan."]),

  "/farmer/harvest": pickByIncludes(HARVEST_PERMISSIONS, [".schedule."]),

  "/voucher/manage-purchase": pickByIncludes(TRANSACTION_TICKET_PERMISSIONS, [
    ".purchase.",
  ]),

  "/voucher/manage-sale": pickByIncludes(TRANSACTION_TICKET_PERMISSIONS, [
    ".sale.",
  ]),

  "/voucher": withAllPermissions(TRANSACTION_TICKET_PERMISSIONS),

  "/support/land": withAllPermissions(INSPECTOR_PERMISSIONS),

  "/connection": [],
  "/connection/statistics-connect": [],
  "/connection/statistics-share": [],

  "/route-manage": withAllPermissions(TRANSPORTATION_ROUTE_PERMISSIONS),

  "/route-manage/vehicle": withAllPermissions(VEHICLE_PERMISSIONS),

  "/factory": withAllPermissions(FACTORY_PERMISSIONS),

  "/factory/receive-material": withAllPermissions(
    RAW_MATERIAL_RELEASE_PERMISSIONS,
  ),

  "/factory/ticket-product": withAllPermissions(PRODUCTION_ORDER_PERMISSIONS),

  "/factory/production-management": withAllPermissions(
    RAW_MATERIAL_TANK_PERMISSIONS,
    PRODUCTION_ORDER_PERMISSIONS,
    FINISHED_GOODS_RECEIPT_PERMISSIONS,
    PALLET_PERMISSIONS,
  ),

  "/factory/fg-receipt-summary": withAllPermissions(
    FINISHED_GOODS_RECEIPT_PERMISSIONS,
  ),

  "/tank/raw-material": withAllPermissions(RAW_MATERIAL_TANK_PERMISSIONS),

  "/tank/product": withAllPermissions(PRODUCT_TANK_PERMISSIONS),

  "/product-type": withAllPermissions(PRODUCT_TYPE_PERMISSIONS),

  "/management/company": withAllPermissions(COMPANY_PERMISSIONS),

  "/management/group-permission": withAllPermissions(COMPANY_GROUP_PERMISSIONS),

  "/management/user-management": withAllPermissions(COMPANY_MEMBER_PERMISSIONS),

  "/custom-field": withAllPermissions(CUSTOM_FIELD_PERMISSIONS),

  "/sale/customer": withAllPermissions(SALES_CUSTOMER_PERMISSIONS),
  "/sale/order": withAllPermissions(SALES_ORDER_PERMISSIONS),
  "/sale/issue": withAllPermissions(SALES_ISSUE_PERMISSIONS),
  "/trader/packing-price": withAllPermissions(
    PALLET_PERMISSIONS,
    PRICE_PERMISSIONS,
  ),
};
