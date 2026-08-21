export interface IPalletData {
  pallet_code: string;
  warehouse_id: number;
  rubber_block_ids: number[]; // Dùng khi update
}

export interface IPalletItem {
  pallet_item_id: number;
  pallet_id: number;
  rubber_block_id: number;
  added_at: string;
  rubber_block_code: string;
  weight: string;
  grade: string;
  status: string;
}

export interface IPallet {
  pallet_id: number;
  pallet_code: string;
  warehouse_id: number;
  status: string;
  total_bales: number;
  total_weight: number;
  packed_at: string;
  shipped_at: string;
  created_at: string;
  company_id: number;
  created_by: number;
  updated_at: string;
  updated_by: number;
  items?: IPalletItem[];
}
