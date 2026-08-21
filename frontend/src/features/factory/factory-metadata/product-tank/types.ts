export interface IProductTank {
  product_tank_id: number;
  product_tank_code: string;
  product_tank_name: string;
  factory_id: number;
  factory_name: string;
  product_type: string;
  capacity: number;
  current_volume: number;
  location: string;
  status: string;
  notes: string;
  created_at: string;
}

export interface IProductTankData {
  product_tank_name: string; // Tên bồn chứa
  factory_id: number; // ID nhà máy
  product_type: string; // Loại thành phẩm: SVR 3L, SVR 5, SVR 10, SVR 20, etc.
  capacity: number; // Dung tích tối đa (kg) của bồn chứa
  location: string; // Vị trí của bồn chứa trong nhà máy
  notes: string; // Ghi chú nếu có
}

export interface IProductTankHistory {
  product_tank_history_id: number;
  product_tank_id: number;
  entity_type: string;
  entity_id: number;
  action_type: string;
  product_type_category: string;
  product_type_id: number;
  quantity: number;
  weight: number;
  volume_before: number;
  volume_after: number;
  created_at: string;
  created_by: number;
  notes: string;
}
