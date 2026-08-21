export interface ITransportationRoute {
  transportation_route_id: number;
  transportation_route_code: string;
  vehicle_id: number;
  vehicle_name: string;
  vehicle_license_plate: string;
  driver_id: number;
  driver_name: string;
  transport_date: string;
  pickup_time: string;
  source_type: string;
  source_transaction_tickets: { transaction_ticket_id: number }[];
  source_factory_id: number;
  destination_factory_id: number;
  destination_factory_name: string;
  destination_raw_material_tank_id: number;
  status: string;
  created_at: string;
}

export interface ITransportationRouteData {
  vehicle_id: number; // ID xe chở - Thông tin xe chở
  driver_id: number; // ID tài xế - Thông tin tài xế (Nếu có)
  driver_name: string; // Tên tài xế
  transport_date: string; // Ngày vận chuyển
  pickup_time: string; // Giờ lấy hàng
  source_type: string; // Nguồn: phiếu mua, nhà máy, ...
  source_transaction_ticket_ids: number[]; // Danh sách ID phiếu mua
  destination_factory_id: number; // ID Nhà máy
  destination_raw_material_tank_id: number; // ID bồn chứa nguyên liệu thô (Nếu có)
}

export interface ITransportationRouteDetail {
  transportation_route_id: number;
  transportation_route_code: string;
  vehicle_id: number;
  vehicle_name: string;
  vehicle_license_plate: string;
  driver_id: number;
  driver_name: string;
  transport_date: string;
  pickup_time: string;
  source_type: string;

  source_transaction_tickets: ITransactionTicket[];

  source_factory_id: number;
  destination_factory_id: number;
  destination_factory_name: string;
  destination_raw_material_tank_id: number;

  status: string;
  created_at: string;
}

export interface ITransactionTicket {
  transaction_ticket_id: number;
  transaction_ticket_code: string;
  transaction_ticket_type: string;
  contract_code: string;

  connection_id: number;

  buyer_company_id: number;
  buyer_user_id: number;
  buyer_name: string;
  buyer_phone: string;
  buyer_account_type: string;
  buyer_address: string;

  seller_company_id: number;
  seller_user_id: number;
  seller_name: string;
  seller_phone: string;
  seller_account_type: string;
  seller_address: string;

  latex_weight: string;
  latex_tsc_grade: string;
  latex_price_per_tsc: number;
  latex_total_amount: number;
  latex_notes: string;

  scrap_rubber_weight: string;
  scrap_rubber_drc_grade: string;
  scrap_rubber_price_per_drc: number;
  scrap_rubber_total_amount: number;
  scrap_rubber_notes: string;

  payment_terms: string;
  delivery_terms: string;

  status: string;

  sent_at: string;
  responded_at: string | null;
  completed_at: string | null;
  rejection_reason: string | null;

  created_at: string;
  created_by: number;
  updated_at: string | null;
  updated_by: number;
  deleted_at: string | null;
  deleted_by: number;
}
