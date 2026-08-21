export interface IProductionOrder {
  production_order_id: number;
  production_order_code: string;
  production_order_name: string;

  contract_id: number;
  contract_code: string;

  product_type_category: "latex" | "scrap_rubber";
  product_type_id: number;
  product_type_name: string;

  required_quantity: number;
  production_date: string;

  status: "approved" | "in_production" | "completed" | string;
  created_at: string;
}

export interface IProductionOrderData {
  production_order_name: string; // Tên phiếu sản xuất / Lệnh sản xuất
  production_order_code: string; // Mã phiếu sản xuất / Lệnh sản xuất
  contract_id: number; // ID Hợp đồng (Nếu có)
  contract_code: string; // Mã Hợp đồng (Nếu có)
  product_type_category: string; // Danh mục loại sản phẩm mủ tạp: scrap_rubber, Kem: concentrated_latex
  product_type_id: number; // ID loại sản phẩm
  required_quantity: number; // Số lượng yêu cầu
  production_date: string; // Ngày sản xuất
}
