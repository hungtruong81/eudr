"use client";

import {
  // core
  hasPermission,

  // user
  USER_PERMISSIONS,

  // land / worker / plant
  LAND_PERMISSIONS,
  WORKER_PERMISSIONS,
  PLANT_PERMISSIONS,

  // harvest
  HARVEST_PERMISSIONS,

  // transaction
  TRANSACTION_TICKET_PERMISSIONS,

  COMPANY_PERMISSIONS,
  COMPANY_GROUP_PERMISSIONS,
  COMPANY_MEMBER_PERMISSIONS,
  CUSTOM_FIELD_PERMISSIONS,

  // production
  FACTORY_PERMISSIONS,
  FINISHED_GOODS_RECEIPT_PERMISSIONS,
  PRODUCTION_ORDER_PERMISSIONS,
  RAW_MATERIAL_RELEASE_PERMISSIONS,
  RAW_MATERIAL_TANK_PERMISSIONS,
  PRODUCT_TANK_PERMISSIONS,
  PRODUCT_TYPE_PERMISSIONS,
  TRANSPORTATION_ROUTE_PERMISSIONS,
  VEHICLE_PERMISSIONS,

  // trader
  SALES_CUSTOMER_PERMISSIONS,
  SALES_ORDER_PERMISSIONS,
  SALES_ISSUE_PERMISSIONS,
  PALLET_PERMISSIONS,
  PRICE_PERMISSIONS,
} from "@/lib/permissions";

import { useUser } from "@/providers/user-context";
import { createContext, useContext, useMemo } from "react";

export type CRUD = {
  create: boolean;
  view: {
    self?: boolean;
    own?: boolean;
    all?: boolean;
  };
  update: {
    self?: boolean;
    own?: boolean;
    all?: boolean;
  };
  delete: {
    self?: boolean;
    own?: boolean;
    all?: boolean;
  };
  full: boolean;
};

type PermissionContextType = {
  user: {
    approve: boolean;
    update: boolean;
    delete: boolean;
    updatePermission: boolean;
  };

  land: CRUD;
  worker: CRUD;
  plant: CRUD;

  company: {
    create: boolean;
    view: { all: boolean };
    update: { all: boolean };
    delete: { all: boolean };
    full: boolean;
  };

  companyGroup: CRUD;
  companyMember: CRUD;
  custom_field: CRUD;

  factory: CRUD;
  finishedGoodsReceipt: CRUD;
  productionOrder: CRUD;
  rawMaterialRelease: CRUD;
  rawMaterialTank: CRUD;
  productTank: CRUD;
  productType: CRUD;
  transportationRoute: CRUD;
  vehicle: CRUD;

  harvest: {
    plan: CRUD;
    schedule: CRUD;
    result: {
      view: CRUD["view"];
      update: CRUD["update"];
      full: boolean;
    };
  };

  transactionTicket: {
    purchase: CRUD;
    sale: CRUD;
    full: boolean;
  };

  trader: {
    customer: CRUD;
    order: CRUD;
    pallet: CRUD & {
      cancel: {
        self?: boolean;
        own?: boolean;
        all?: boolean;
      };
      pack: {
        self?: boolean;
        own?: boolean;
        all?: boolean;
      };
      ship: {
        self?: boolean;
        own?: boolean;
        all?: boolean;
      };
    };
    price: CRUD;
    issue: {
      create: boolean;
      view: CRUD["view"];
      update: CRUD["update"];
      delete: CRUD["delete"];
      issue: {
        self?: boolean;
        own?: boolean;
        all?: boolean;
      };
      cancel: {
        self?: boolean;
        own?: boolean;
        all?: boolean;
      };
      full: boolean;
    };
  };
};

const PermissionContext = createContext<PermissionContextType | undefined>(
  undefined
);

const mapCRUD = (can: any, p: any): CRUD => ({
  create: can(p.CREATE, p.FULL),
  view: {
    self: p.VIEW_SELF && can(p.VIEW_SELF, p.FULL),
    own: p.VIEW_OWN && can(p.VIEW_OWN, p.FULL),
    all: p.VIEW_ALL && can(p.VIEW_ALL, p.FULL),
  },
  update: {
    self: p.UPDATE_SELF && can(p.UPDATE_SELF, p.FULL),
    own: p.UPDATE_OWN && can(p.UPDATE_OWN, p.FULL),
    all: p.UPDATE_ALL && can(p.UPDATE_ALL, p.FULL),
  },
  delete: {
    self: p.DELETE_SELF && can(p.DELETE_SELF, p.FULL),
    own: p.DELETE_OWN && can(p.DELETE_OWN, p.FULL),
    all: p.DELETE_ALL && can(p.DELETE_ALL, p.FULL),
  },
  full: can(p.FULL),
});

export const PermissionProvider = ({
  children,
}: {
  children: React.ReactNode;
}) => {
  const { userInfo } = useUser();
  const userPermissions = userInfo?.permissions || [];

  const can = (perm?: string, full?: string) =>
    !!perm &&
    (hasPermission(userPermissions, perm) ||
      (full ? hasPermission(userPermissions, full) : false));

  const permissions = useMemo<PermissionContextType>(
    () => ({
      /* ===== USER ===== */
      user: {
        approve: can(USER_PERMISSIONS.APPROVE, USER_PERMISSIONS.FULL),
        update: can(USER_PERMISSIONS.UPDATE, USER_PERMISSIONS.FULL),
        delete: can(USER_PERMISSIONS.DELETE, USER_PERMISSIONS.FULL),
        updatePermission: can(
          USER_PERMISSIONS.PERMISSION_UPDATE,
          USER_PERMISSIONS.FULL
        ),
      },

      /* ===== BASIC ===== */
      land: mapCRUD(can, LAND_PERMISSIONS),
      worker: mapCRUD(can, WORKER_PERMISSIONS),
      plant: mapCRUD(can, PLANT_PERMISSIONS),

      /* ===== COMPANY ===== */
      company: {
        create: can(COMPANY_PERMISSIONS.CREATE, COMPANY_PERMISSIONS.FULL),
        view: {
          all: can(COMPANY_PERMISSIONS.VIEW_ALL, COMPANY_PERMISSIONS.FULL),
        },
        update: {
          all: can(COMPANY_PERMISSIONS.UPDATE_ALL, COMPANY_PERMISSIONS.FULL),
        },
        delete: {
          all: can(COMPANY_PERMISSIONS.DELETE_ALL, COMPANY_PERMISSIONS.FULL),
        },
        full: can(COMPANY_PERMISSIONS.FULL),
      },

      companyGroup: mapCRUD(can, COMPANY_GROUP_PERMISSIONS),
      companyMember: mapCRUD(can, COMPANY_MEMBER_PERMISSIONS),
      custom_field: mapCRUD(can, CUSTOM_FIELD_PERMISSIONS),

      /* ===== PRODUCTION ===== */
      factory: mapCRUD(can, FACTORY_PERMISSIONS),
      finishedGoodsReceipt: mapCRUD(can, FINISHED_GOODS_RECEIPT_PERMISSIONS),
      productionOrder: mapCRUD(can, PRODUCTION_ORDER_PERMISSIONS),
      rawMaterialRelease: mapCRUD(can, RAW_MATERIAL_RELEASE_PERMISSIONS),
      rawMaterialTank: mapCRUD(can, RAW_MATERIAL_TANK_PERMISSIONS),
      productTank: mapCRUD(can, PRODUCT_TANK_PERMISSIONS),
      productType: mapCRUD(can, PRODUCT_TYPE_PERMISSIONS),
      transportationRoute: mapCRUD(can, TRANSPORTATION_ROUTE_PERMISSIONS),
      vehicle: mapCRUD(can, VEHICLE_PERMISSIONS),

      /* ===== HARVEST ===== */
      harvest: {
        plan: mapCRUD(can, {
          CREATE: HARVEST_PERMISSIONS.PLAN_CREATE,
          VIEW_SELF: HARVEST_PERMISSIONS.PLAN_VIEW_SELF,
          VIEW_OWN: HARVEST_PERMISSIONS.PLAN_VIEW_OWN,
          VIEW_ALL: HARVEST_PERMISSIONS.PLAN_VIEW_ALL,
          UPDATE_SELF: HARVEST_PERMISSIONS.PLAN_UPDATE_SELF,
          UPDATE_OWN: HARVEST_PERMISSIONS.PLAN_UPDATE_OWN,
          UPDATE_ALL: HARVEST_PERMISSIONS.PLAN_UPDATE_ALL,
          DELETE_SELF: HARVEST_PERMISSIONS.PLAN_DELETE_SELF,
          DELETE_OWN: HARVEST_PERMISSIONS.PLAN_DELETE_OWN,
          DELETE_ALL: HARVEST_PERMISSIONS.PLAN_DELETE_ALL,
          FULL: HARVEST_PERMISSIONS.PLAN_FULL,
        }),
        schedule: mapCRUD(can, {
          CREATE: HARVEST_PERMISSIONS.SCHEDULE_CREATE,
          VIEW_SELF: HARVEST_PERMISSIONS.SCHEDULE_VIEW_SELF,
          VIEW_OWN: HARVEST_PERMISSIONS.SCHEDULE_VIEW_OWN,
          VIEW_ALL: HARVEST_PERMISSIONS.SCHEDULE_VIEW_ALL,
          UPDATE_SELF: HARVEST_PERMISSIONS.SCHEDULE_UPDATE_SELF,
          UPDATE_OWN: HARVEST_PERMISSIONS.SCHEDULE_UPDATE_OWN,
          UPDATE_ALL: HARVEST_PERMISSIONS.SCHEDULE_UPDATE_ALL,
          DELETE_SELF: HARVEST_PERMISSIONS.SCHEDULE_DELETE_SELF,
          DELETE_OWN: HARVEST_PERMISSIONS.SCHEDULE_DELETE_OWN,
          DELETE_ALL: HARVEST_PERMISSIONS.SCHEDULE_DELETE_ALL,
          FULL: HARVEST_PERMISSIONS.SCHEDULE_FULL,
        }),
        result: {
          view: {
            self: can(
              HARVEST_PERMISSIONS.RESULT_VIEW_SELF,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
            own: can(
              HARVEST_PERMISSIONS.RESULT_VIEW_OWN,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
            all: can(
              HARVEST_PERMISSIONS.RESULT_VIEW_ALL,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
          },
          update: {
            self: can(
              HARVEST_PERMISSIONS.RESULT_UPDATE_SELF,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
            own: can(
              HARVEST_PERMISSIONS.RESULT_UPDATE_OWN,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
            all: can(
              HARVEST_PERMISSIONS.RESULT_UPDATE_ALL,
              HARVEST_PERMISSIONS.RESULT_FULL
            ),
          },
          full: can(HARVEST_PERMISSIONS.RESULT_FULL),
        },
      },

      /* ===== TRANSACTION ===== */
      transactionTicket: {
        purchase: mapCRUD(can, {
          CREATE: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_CREATE,
          VIEW_OWN: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_VIEW_OWN,
          VIEW_ALL: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_VIEW_ALL,
          UPDATE_OWN: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_UPDATE_OWN,
          UPDATE_ALL: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_UPDATE_ALL,
          DELETE_OWN: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_DELETE_OWN,
          DELETE_ALL: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_DELETE_ALL,
          FULL: TRANSACTION_TICKET_PERMISSIONS.PURCHASE_FULL,
        }),

        sale: mapCRUD(can, {
          CREATE: TRANSACTION_TICKET_PERMISSIONS.SALE_CREATE,
          VIEW_OWN: TRANSACTION_TICKET_PERMISSIONS.SALE_VIEW_OWN,
          VIEW_ALL: TRANSACTION_TICKET_PERMISSIONS.SALE_VIEW_ALL,
          UPDATE_OWN: TRANSACTION_TICKET_PERMISSIONS.SALE_UPDATE_OWN,
          UPDATE_ALL: TRANSACTION_TICKET_PERMISSIONS.SALE_UPDATE_ALL,
          DELETE_OWN: TRANSACTION_TICKET_PERMISSIONS.SALE_DELETE_OWN,
          DELETE_ALL: TRANSACTION_TICKET_PERMISSIONS.SALE_DELETE_ALL,
          FULL: TRANSACTION_TICKET_PERMISSIONS.SALE_FULL,
        }),

        full:
          can(TRANSACTION_TICKET_PERMISSIONS.PURCHASE_FULL) ||
          can(TRANSACTION_TICKET_PERMISSIONS.SALE_FULL),
      },

      trader: {
        customer: mapCRUD(can, SALES_CUSTOMER_PERMISSIONS),
        order: mapCRUD(can, SALES_ORDER_PERMISSIONS),
        pallet: {
          ...mapCRUD(can, PALLET_PERMISSIONS),
          cancel: {
            self: can(PALLET_PERMISSIONS.CANCEL_SELF, PALLET_PERMISSIONS.FULL),
            own: can(PALLET_PERMISSIONS.CANCEL_OWN, PALLET_PERMISSIONS.FULL),
            all: can(PALLET_PERMISSIONS.CANCEL_ALL, PALLET_PERMISSIONS.FULL),
          },
          pack: {
            self: can(PALLET_PERMISSIONS.PACK_SELF, PALLET_PERMISSIONS.FULL),
            own: can(PALLET_PERMISSIONS.PACK_OWN, PALLET_PERMISSIONS.FULL),
            all: can(PALLET_PERMISSIONS.PACK_ALL, PALLET_PERMISSIONS.FULL),
          },
          ship: {
            self: can(PALLET_PERMISSIONS.SHIP_SELF, PALLET_PERMISSIONS.FULL),
            own: can(PALLET_PERMISSIONS.SHIP_OWN, PALLET_PERMISSIONS.FULL),
            all: can(PALLET_PERMISSIONS.SHIP_ALL, PALLET_PERMISSIONS.FULL),
          },
        },
        price: mapCRUD(can, PRICE_PERMISSIONS),
        issue: {
          create: can(
            SALES_ISSUE_PERMISSIONS.CREATE,
            SALES_ISSUE_PERMISSIONS.FULL
          ),

          view: {
            self: can(
              SALES_ISSUE_PERMISSIONS.VIEW_SELF,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            own: can(
              SALES_ISSUE_PERMISSIONS.VIEW_OWN,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            all: can(
              SALES_ISSUE_PERMISSIONS.VIEW_ALL,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
          },

          update: {
            self: can(
              SALES_ISSUE_PERMISSIONS.UPDATE_SELF,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            own: can(
              SALES_ISSUE_PERMISSIONS.UPDATE_OWN,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            all: can(
              SALES_ISSUE_PERMISSIONS.UPDATE_ALL,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
          },

          delete: {
            self: can(
              SALES_ISSUE_PERMISSIONS.DELETE_SELF,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            own: can(
              SALES_ISSUE_PERMISSIONS.DELETE_OWN,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            all: can(
              SALES_ISSUE_PERMISSIONS.DELETE_ALL,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
          },

          issue: {
            self: can(
              SALES_ISSUE_PERMISSIONS.ISSUE_SELF,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            own: can(
              SALES_ISSUE_PERMISSIONS.ISSUE_OWN,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            all: can(
              SALES_ISSUE_PERMISSIONS.ISSUE_ALL,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
          },

          cancel: {
            self: can(
              SALES_ISSUE_PERMISSIONS.CANCEL_SELF,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            own: can(
              SALES_ISSUE_PERMISSIONS.CANCEL_OWN,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
            all: can(
              SALES_ISSUE_PERMISSIONS.CANCEL_ALL,
              SALES_ISSUE_PERMISSIONS.FULL
            ),
          },

          full: can(SALES_ISSUE_PERMISSIONS.FULL),
        },
      },
    }),
    [userPermissions]
  );

  return (
    <PermissionContext.Provider value={permissions}>
      {children}
    </PermissionContext.Provider>
  );
};

export const usePermissions = () => {
  const ctx = useContext(PermissionContext);
  if (!ctx) {
    throw new Error("usePermissions must be used within PermissionProvider");
  }
  return ctx;
};
