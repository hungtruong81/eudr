export interface ICustomer {
  customer_id: number;
  customer_code: string;
  customer_name: string;
  customer_company_name: string;
  customer_email: string;
  customer_phone: string;
  company_id: number;
  business_license_file_ids: number[];
  business_license_file_urls: string[];
  tax_code: string;
  billing_address: string;
  shipping_address: string;
  customer_type: string;
  status: string;
  notes: string;
  created_at: string;
  created_by: number;
  updated_at: string;
  updated_by: number;
}

export interface ICustomerData {
  customer_code: string; // Mã Khách hàng
  customer_company_name: string; // Tên công ty
  customer_name: string; // Tên Khách Hàng
  customer_email: string; // Địa chỉ email KH
  customer_phone: string; // Số điện thoại KH
  tax_code: string; // Mã Số Thuế
  billing_address: string; // Địa chỉ xuất hóa đơn
  shipping_address: string; // Địa chỉ giao hàng
  customer_type: string; // Phân loại KH
  business_license_file_ids: number[]; // Giấy phép đăng ký kinh doanh
  notes: string; // Ghi chú nếu có
}
