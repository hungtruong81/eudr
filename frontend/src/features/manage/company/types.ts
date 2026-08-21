export interface ICompany {
  company_id: number;
  company_code: string;
  company_name: string;
  short_name: string;
  tax_code: string;
  address: string;
  website: string;
  status: string;
  created_at: string;
  member_count: number;
}

export interface ICompanyData {
  company_name: string; // Tên công ty
  short_name: string; // Tên công ty viết tắt
  tax_code: string; // Mã số thuế
  address: string; // Địa chỉ
  website: string;
}
