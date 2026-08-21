export interface ITransactionTicket {
  transaction_ticket_id: number;
  transaction_ticket_code: string;
  transaction_ticket_type: string;
  seller_user_id: number;
  seller_name: string;
  seller_phone: string;
  seller_account_type: string;
  buyer_user_id: number;
  buyer_name: string;
  buyer_phone: string;
  buyer_account_type: string;
  ticket_latex_weight: number;
  ticket_scrap_rubber_weight: number;
  ticket_status: string;
  allocated_latex_weight: number;
  allocated_scrap_weight: number;
  estimated_harvest_date: string | null;
  actual_harvest_date: string | null;
}

export interface ITraceFarm {
  plot_id: number;
  plot_code: string;
  plot_name: string;
  farmer_user_id: number;
  farmer_name: string;
  company_id: number;
  company_name: string | null;
  province_id: number;
  address: string;
  country: string;
  land_area: number;
  coordinates: string;
  ownership: string;
  classify: string;
  eudr_status: number;
  maximum_yield: number;
  area_24: number;
  land_status: string;
  transaction_tickets: ITransactionTicket[];
}

export interface ITraceProductLot {
  product_lot_id: number;
  product_lot_code: string;
  grade: string;
  factory_id: number;
  owner_company_id: number;
  factory_name: string | null;
  factory_code: string | null;
  production_date_from: string;
  production_date_to: string;
  total_blocks: number;
  total_weight: string;
  status: string;
  confirmed_at: string | null;
}

export interface IProductLotTraceability {
  result: string;
  trace_id: string;
  product_lot: ITraceProductLot;
  total_farms: number;
  farms: ITraceFarm[];
}
