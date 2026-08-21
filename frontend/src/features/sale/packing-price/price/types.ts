export interface IPriceData {
  price_name: string;
  price_type: string;
  domestic_price: string;
  international_price: string;
}

export interface IPrice {
  price_id: number;
  price_code: string;
  price_name: string;
  price_type: string;
  domestic_price: number;
  international_price: number;
  company_id: number;
  created_at: string;
  created_by: number;
  updated_at: string;
  updated_by: number;
  deleted_at: string | null;
  deleted_by: number;
}
