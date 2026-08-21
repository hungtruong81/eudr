export interface IRawMaterialRelease {
  material_release_id: number;
  material_release_code: string;
  material_release_name: string;
  production_order_id: number;
  production_order_code: string;
  production_order_name: string;
  total_requested_weight: string;
  raw_material_tanks: [];
  status: string;
  created_at: string;
}

export interface IRawMaterialReleaseItem {
  material_release_item_id: number;
  raw_material_tank_id: number;
  raw_material_tank_code: string;
  raw_material_tank_name: string;
  capacity: string;
  current_volume: string;
  rubber_type: string;
  weight_requested: string;
  notes: string;
}

export interface IRawMaterialReleaseByCode {
  material_release_id: number;
  material_release_code: string;
  material_release_name: string;
  production_order_id: number;
  production_order_code: string;
  production_order_name: string;
  total_requested_weight: string;
  raw_material_tanks: IRawMaterialReleaseItem[];
}

export interface IRawMaterialReleaseItemData {
  tank_id: number;
  weight_requested: number;
  rubber_type: string;
  notes: string;
}
export interface IRawMaterialReleaseData {
  material_release_name: string;
  material_release_code: string;
  production_order_id: number;
  raw_material_tanks: IRawMaterialReleaseItemData[];
}
