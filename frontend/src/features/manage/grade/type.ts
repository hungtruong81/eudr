export interface IGrade {
  grade_id: number;
  grade_code: string;
  name: string;
  description: string;
  created_at: string;
  updated_at: string;
  current_domestic_price: number;
  current_international_price: number;
  current_price_effective_from: string;
  current_price_effective_to: string;
}

export interface IGradeData {
  grade_code: string;
  name: string;
  description: string;
}

export interface IGradePriceData {
  domestic_price: number; // required, Giá trong nước
  international_price: number; // required, Giá quốc tế (Tiền đô)
  effective_from: string; // required, Thời gian hiệu lực từ
  effective_to: string; // optional, Thời gian hết hiệu lực
  note: string; // Ghi chú nếu có
}

export interface IGradePrice {
  grade_price_id: number;
  domestic_price: string;
  international_price: string;
  effective_from: string;
  effective_to: string | null;
  note: string;
}

export interface IGradePriceCurrent {
  grade_id: number;
  grade_code: string;
  at_datetime: string;
  price: IGradePrice;
}

export interface IGradePriceHistory extends IGradePrice {}
