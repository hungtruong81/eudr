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

export type PermissionContextType = {
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
  custom_field: CRUD;

  companyMember: {
    create: boolean;
    view: {
      self: boolean;
      own: boolean;
      all: boolean;
    };
    update: {
      self: boolean;
      own: boolean;
      all: boolean;
    };
    delete: {
      self: boolean;
      own: boolean;
      all: boolean;
    };
    full: boolean;
  };

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
};
