export interface IIssueAllocation {
  issue_allocation_id: number;
  issue_item_id: number;
  sale_order_item_id: number;
  product_tank_id: number;
  raw_material_tank_id: number;
  transaction_ticket_id: number;
  raw_material_tank_code: string;
  raw_material_tank_name: string;
  product_tank_code?: string;
  product_tank_name?: string;
  transaction_ticket_code?: string;
  ticket_seller_name?: string;
  lot_id: number | null;
  qty_issued: number;
  weight_issued: number | null;
  notes: string | null;
}

export interface IIssueItem {
  issue_item_id: number;
  issue_id: number;
  sale_order_item_id: number;
  company_id: number;
  product_id: number;
  uom: string;
  qty_issued: number;
  price: number;
  currency: string;
  notes: string | null;
  allocations: IIssueAllocation[];
}
export interface IIssue {
  issue_id: number;
  issue_code: string;
  sale_order_id: number;
  sale_order_code?: string;
  company_id: number;
  warehouse_id: number | null;
  issue_date: string;
  status: string;
  document_ref: string | null;
  shipper: string;
  vehicle_no: string;
  receiver: string;
  reason_code: string | null;
  notes: string;
  created_at: string;
  created_by: number;
  updated_at: string | null;
  updated_by: number;
  cancelled_at: string | null;
  cancelled_by: number;
  deleted_at: string | null;
  deleted_by: number;
  items: IIssueItem[];
}

export interface IIssueData {
  issue_code: string; // Mã phiếu
  sale_order_id: number; // ID đơn hàng
  // warehouse_id?: number; // Kho xuất
  issue_date: string; // Ngày/giờ xuất
  // document_ref?: string; // Số chứng từ/đơn giao ngoài
  shipper: string; // Hãng vận chuyển/tài xế
  vehicle_no: string; // Biển số xe
  receiver: string; // Người/đơn vị nhận
  // reason_code?: string; // Mã lý do xuất (nếu dùng)
  notes: string;
  items: IIssueItemData[];
}

export interface IIssueItemData {
  sale_order_item_id: number; // Tham chiếu dòng đơn hàng
  product_id: number; // ID loại thành phẩm
  uom: string; // Đơn vị tính
  qty_issued: number; // Số lượng xuất
  price: number; // Giá tại thời điểm xuất (nếu cần)
  currency: string; // Tiền tệ (nếu lưu giá)
  notes: string;
  allocations: IIssueAllocationData[];
}

export interface IIssueAllocationData {
  product_tank_id: number; // ID Bồn thành phẩm
  raw_material_tank_id: number; // dùng cho mủ thô
  transaction_ticket_id: number; // liên kết phiếu mua/bán
  // lot_id?: number; // Lô/batch (nếu có)
  qty_issued: number; // Số lượng xuất từ bồn/lô
  weight_issued?: number; // Khối lượng xuất (nếu cần)
  notes?: string;
}
