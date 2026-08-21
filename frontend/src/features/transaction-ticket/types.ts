export interface ITransactionTicket {
  transaction_ticket_id: number;
  transaction_ticket_code: string;
  transaction_ticket_type: string;

  contract_code: string;
  connection_id: number;

  buyer_user_id: number;
  buyer_name: string;
  buyer_phone: string;
  buyer_account_type: string;
  buyer_address: string;
  buyer_company_id: number;
  buyer_company_short_name: string;

  seller_user_id: number;
  seller_name: string;
  seller_phone: string;
  seller_account_type: string;
  seller_address: string;
  seller_company_id: number;
  seller_company_short_name: string;

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

  created_at: string;
  created_by: number;

  usage_count: number;
}

export interface ITransactionTicketPayload {
  transaction_ticket_type: "purchase" | "sale";
  connection_id?: number;

  buyer_user_id?: number;
  buyer_name?: string;
  buyer_phone?: string;
  buyer_account_type?: "purchaser" | "trader" | "company" | string;
  buyer_address?: string;

  seller_user_id?: number;
  seller_name?: string;
  seller_phone?: string;
  seller_account_type?: "farmer" | "purchaser" | "trader" | string;
  seller_address?: string;

  latex_weight?: string | number;
  latex_tsc_grade?: string | number;
  latex_price_per_tsc?: number;
  latex_total_amount?: number;
  latex_notes?: string;

  scrap_rubber_weight?: string | number;
  scrap_rubber_drc_grade?: string | number;
  scrap_rubber_price_per_drc?: number;
  scrap_rubber_total_amount?: number;
  scrap_rubber_notes?: string;

  payment_terms?: string;
  delivery_terms?: string;

  plot_ids?: number[];
  purchase_ticket_ids?: number[];
}
