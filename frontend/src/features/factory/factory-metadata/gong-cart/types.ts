export interface IProductionGongCartData {
  gong_cart_code: string; // Mã xe gioong
  factory_id: number; // ID nhà máy
  gong_cart_name: string; // Tên xe gioong
  max_poles: number; // Số sào treo
}

export interface IProductionGongCart {
  gong_cart_id: number;
  gong_cart_code: string;
  gong_cart_name: string;
  company_id: number;
  factory_id: number;
  factory_name: string;
  max_poles: number;
  status: string; //available,in_use,cleaning,all
  created_at: string;
  updated_at: string | null;
  deleted_at: string | null;
}
