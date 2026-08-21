export interface IProductionRollerData {
  roller_code: string;
  factory_id: number;
  roller_name: string;
}

export interface IProductionRoller {
  roller_id: number;
  roller_code: string;
  roller_name: string;
  company_id: number;
  factory_id: number;
  factory_name: string;
  status: string; //available,in_use,maintenance,all
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}
