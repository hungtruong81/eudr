import { IUserRole } from "@/components/role-tag";

export interface ICompanyGroup {
  company_group_id: number;
  company_group_code: string;
  company_id: number;
  company_code: string;
  company_name: string;
  company_short_name: string;
  name: string;
  description: string;
  is_default: boolean;
  status: string;
  created_at: string;
  created_by: number;
  updated_at: string | null;
  updated_by: number;
  deleted_at: string | null;
  deleted_by: number;
  member_count: number;
}

export interface ICompanyGroupByCode {
  company_group_id: number;
  company_group_code: string;
  company_id: number;
  company_code: string;
  company_name: string;
  company_short_name: string;
  name: string;
  description: string;
  is_default: boolean;
  status: string;
  created_at: string;
  created_by: number;
  updated_at: string | null;
  updated_by: number;
  deleted_at: string | null;
  deleted_by: number;
  member_count: number;
  permissions: string[];
}

export interface ICompanyGroupData {
  name: string; // Tên nhóm người dùng
  description: string; // Mô tả
  //,"company_id": 1111 // Nếu là tài khoản admin hệ thống
}

export interface IGroupMember {
  user_id: number;
  user_code: string;
  full_name: string;
  email: string;
  phone: string;
  avatar: string;
  register_type: string;
  company_id: number;
  user_roles: IUserRole[];
}

export type IUserCompany = IGroupMember;

export interface ISetGroupPermission {
  permissions: string[];
}

export interface IAssignMemberToGroup {
  assign_user_ids: number[];
  remove_user_ids: number[];
}

export interface IPermission {
  permission_id: number;
  name: string;
  display_name: string;
  module: string;
  description: string;
  scope: string;
  action: string;
}
