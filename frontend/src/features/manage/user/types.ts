export interface IUserCompany {
  user_id: number;
  user_code: string;
  company_id: number;
  company_code: string;
  company_name: string;
  company_short_name: string;
  email: string;
  phone: string;
  avatar: string;
  register_type: string;
  roles: unknown[]; // hiện tại API trả về []
  full_name: string;
  is_approved: number;
  is_active: number;
  parent_user_id: number;
  created_at: string;
  updated_at: string | null;
  user_roles: IUserRole[];
}

export interface IUserRole {
  role_id: number;
  name: string;
  description: string;
}

export interface IUserCompanyData {
  full_name: string;
  email: string;
  phone: string;
  password: string;
  register_type: string; // Loại tài khoản
  company_id: number; // Nếu là admin hệ thống tạo thì gửi lên mã công ty, còn nếu là admin của công ty thì mặc định lấy theo mã công ty của tài khoản
}

export interface IUpdateUserCompanyData {
  full_name: string;
  password: string;
  add_roles: string[];
  remove_roles: string[];
}
