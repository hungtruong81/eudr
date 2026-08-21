export interface IRawMaterialTank {
  raw_material_tank_id: number;
  raw_material_tank_code: string;
  raw_material_tank_name: string;
  factory_id: number;
  factory_name: string;
  tank_type: string;
  capacity: number;
  current_volume: number;
  location: string;
  status: string;
  notes: string;
  created_at: string;
}

export interface IHistoryRawMaterialTank {
  raw_material_tank_history_id: number;
  raw_material_tank_id: number;
  entity_type: string;
  entity_id: number;
  action_type: string;
  rubber_type: string;
  weight: string;
  tsc: string;
  volume_before: string;
  volume_after: string;
  notes: string;
  created_at: string;
  created_by: number;
}

export interface IRawMaterialTankData {
  raw_material_tank_name: string; // Tên bồn chứa
  factory_id: number; // ID nhà máy
  tank_type: string; // Loại bồn chứa nguyên liệu: latex, scrap_rubber, mixed
  capacity: number; // Dung tích tối đa (kg) của bồn chứa
  location: string; // Vị trí của bồn chứa trong nhà máy
  notes: string; // Ghi chú nếu có
}
