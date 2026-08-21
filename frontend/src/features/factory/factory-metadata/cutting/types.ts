export interface ICuttingData {
  cutting_machine_code: string;
  factory_id: number;
  cutting_machine_name: string;
}

export interface ICutting {
  cutting_machine_id: number;
  cutting_machine_code: string;
  cutting_machine_name: string;
  company_id: number;
  factory_id: number;
  factory_name: string;
  status: string; //available,in_use,maintenance,all
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}
