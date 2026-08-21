export interface IProductionOvenData {
  oven_code: string; // Mã lò sấy
  factory_id: number; // ID nhà máy
  oven_name: string; // Tên lò sấy
}

export interface IProductionOven {
  oven_id: number;
  oven_code: string;
  oven_name: string;
  company_id: number;
  factory_id: number;
  factory_name: string;
  status: string;
  created_at: string;
  updated_at: string | null;
  deleted_at: string;
}
