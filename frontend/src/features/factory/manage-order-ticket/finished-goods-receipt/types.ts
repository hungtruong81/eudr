export interface IFinishedGoodsReceipt {
  finished_goods_receipt_id: number;
  finished_goods_receipt_code: string;
  finished_goods_receipt_name: string;
  product_type_category: string;
  production_order_id: number;
  production_order_name: string;
  product_type_id: number;
  product_type_name: string;
  product_tank_id: number;
  product_tank_name: string;
  actual_quantity: number;
  actual_weight: string;
  tank_volume_before: string;
  tank_volume_after: string;
  status: string;
  created_at: string;
  rubber_blocks?: IRubberBlockItem[];
}

export interface IRubberBlockItem {
  rubber_block_id: number;
  rubber_block_code: string;
  production_order_id: number;
  production_order_name: string;
  production_order_code: string;
  product_type_id: number;
  product_type_name: string;
  product_type_code: string;
  weight: number;
  grade: string;
  production_date: string;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface IRubberBlock {
  product_type_id: number; // ID bành
  weight: number; // Khối lượng
  grade: string; // Phân loại (VD: SVR 3L)
  quantity: number; // Số lượng bành theo loại
}

export interface IFinishedGoodsReceiptData {
  finished_goods_receipt_name: string; // Tên phiếu nhập kho thành phẩm
  finished_goods_receipt_code: string; // Mã phiếu nhập kho thành phẩm
  production_order_id: number; // ID phiếu sản xuất
  product_tank_id: number; // ID bồn chứa thành phẩm
  product_type_category: string; // scrap_rubber | concentrated_latex
  product_type_id: number; // ID loại sản phẩm
  actual_quantity: number; // Số lượng thực tế
  rubber_blocks: IRubberBlock[];
}
