export interface IProductLot {
  product_lot_id: number;
  product_lot_code: string;
  grade: string;
  factory_id: number;
  factory_name: string;
  factory_code: string;
  production_date_from: string;
  production_date_to: string;
  total_blocks: number;
  total_weight: string;
  lot_type: string;
  status: string;
  confirmed_at: string | null;
  items: RubberBlockItem[];
}

export interface RubberBlockItem {
  product_lot_item_id: number;
  product_lot_id: number;
  rubber_block_id: number;
  rubber_block_code: string;
  product_type_id: number;
  product_type_name: string;
  product_type_code: string;
  production_order_id: number;
  weight_snapshot: string;
  grade_snapshot: string;
  created_at: string;
}

export interface IProductLotData {
  factory_id: number; // Nhà máy sản xuất
  production_order_id: number; // Lệnh sản xuất
  rubber_block_ids: number[]; // Danh sách ID rubber block để tạo product lot
}

export interface IProductLotInventory {
  product_lot_id: number;
  product_lot_code: string;
  lot_type: "external" | string;
  grade: string;
  factory_id: number;
  owner_company_id: number;
  owner_id: number;
  factory_name: string;
  factory_code: string;
  production_date_from: string;
  production_date_to: string;
  total_blocks: number;
  total_weight: string;
  status: "confirmed" | "pending" | "draft" | string;
  confirmed_at: string | null;
  created_by: number;
  updated_by: number;
  eudr_type: "non_eudr" | "eudr" | string;
  supplier_company_name: string;
  supplier_factory_name: string;
  supplier_phone: string;
  supplier_address: string;
  original_product_lot_code: string;
  external_contract_code: string;
  purchase_date: string;
  purchase_amount: number;
  notes: string;
  non_eudr_items: [];
  attachments: [];
  items: [];
}
