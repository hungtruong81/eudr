export interface IOrderItem {
  sale_order_item_id: number;
  sale_order_id: number;
  company_id: number;
  source_type: "finished_product" | "raw_material";
  transaction_ticket_id: number | null;
  raw_material_tank_id: number | null;
  product_tank_id: number | null;
  product_type_id: number;
  rubber_type: string | null;
  quality_grade: string | null;
  product_tank_code: string;
  product_tank_name: string;
  product_type_code: string;
  product_type_name: string;
  product_weight: number;
  product_type_category: string;
  raw_material_tank_code: string | null;
  raw_material_tank_name: string | null;
  transaction_ticket_code: string | null;
  ticket_contract_code: string | null;
  ticket_seller_name: string | null;
  ticket_buyer_name: string | null;
  uom: string;
  qty_ordered: number;
  qty_allocated: number;
  qty_shipped: number;
  price: number;
  discount_rate: number;
  surcharge: number;
  currency: string;
  notes: string;
}
export interface IOrder {
  sale_order_id: number;
  sale_order_code: string;
  company_id: number;
  customer_id: number;
  contract_id: number;
  quotation_id: number;
  order_date: string;
  delivery_date: string;
  order_source_type: string;
  payment_terms: string;
  delivery_address: string;
  currency: string;
  status: string;
  total_amount: number;
  notes: string;
  created_at: string;
  created_by: number;
  updated_at: string;
  updated_by: number;
  customer_code: string;
  customer_name: string;
  customer_phone: string;
  customer_email: string;
  customer_company_name: string;
  buyer_company_name: string;
  buyer_company_code: string;
  buyer_user_name: string;
  tax_code: string;
  customer_type: string;
  items: IOrderItem[];
}
export type FinishedProductItem = {
  source_type: "finished_product"; // finished_product|raw_material, auto theo order_source_type
  product_tank_id: number; // ID bồn chứa thành phẩm
  product_type_id: number; // ID loại sản phẩm,
  qty_ordered: number; // Số lượng đặt
  price: number; // Đơn giá
  notes: string; // Ghi chú (Nếu có)
};

export type RawMaterialItem = {
  source_type: "raw_material"; // finished_product|raw_material, auto theo order_source_type
  transaction_ticket_id: number; // dùng khi source_type = raw_material
  raw_material_tank_id: number; // dùng khi source_type = raw_material
  product_type_id: number; // ID loại sản phẩm,
  qty_ordered: number; // Số lượng đặt
  price: number; // Đơn giá
  notes: string; // Ghi chú (Nếu có)
};

export interface IOrderData {
  sale_order_code: string;
  customer_code: string; // Mã Khách hàng
  delivery_date: string; // Ngày giao hàng
  order_source_type: string; // Nguồn từ kho thành phẩm: warehouse -  hay phiếu mua/bán: transaction_ticket
  delivery_address: string; // Địa chỉ giao hàng
  notes: string; // Ghi chú (Nếu có)

  items: (FinishedProductItem | RawMaterialItem)[];
}
