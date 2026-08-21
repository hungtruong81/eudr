export interface IProductionChannelData {
  channel_code: string; // Mã mương
  factory_id: number; // ID nhà máy
  channel_name: string; // Tên mương
  capacity_kg: number; // Sức chứa tối đa (kg)
}

export interface IProductionChannel {
  channel_id: number;
  channel_code: string;
  channel_name: string;
  company_id: number;
  factory_id: number;
  factory_name: string;
  capacity_kg: number;
  status: string; // available, in_use, cleaning, all
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface IProductionChannelRun {
  channel_run_id: number;
  production_order_id: number;
  company_id: number;
  factory_id: number;
  raw_tank_id: number;
  channel_id: number;
  input_latex_kg: string;
  input_quality_note: string | null;
  input_ph: string | null;
  coagulation_done: 0 | 1;
  output_ready_for_cutting_kg: string;
  started_at: string;
  ended_at: string | null;
  status: string;
  notes: string | null;
  created_by: number;
  created_at: string;
  updated_by: number;
  updated_at: string | null;
  deleted_by: number;
  deleted_at: string | null;
  channel_code: string;
  channel_name: string;
  raw_material_tank_code: string;
  raw_material_tank_name: string;
}
