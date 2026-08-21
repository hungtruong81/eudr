import { IUserRole } from "@/components/role-tag";

export interface IConnection {
  connection_id: number;
  connection_code: string;
  requester_company_id: number;
  requester_user_id: number;
  target_company_id: number;
  target_user_id: number;
  connection_method: string;
  status: string;
  requested_at: string;
  notes: string;
  created_at: string;
  responded_at: string;
  updated_at: string;
  updated_by: number;
  rejection_reason: string | null;
  is_deleted: number;
  user_code: string;
  phone: string;
  full_name: string;
  email: string;
  register_type: string;
  connection_direction: string;
  user_roles: IUserRole[];
}

export interface ISearchUser {
  user_id: number;
  user_code: string;
  full_name: string;
  email: string;
  phone: string;
  register_type: string;
  created_at: string;
  user_roles: [
    {
      role_id: number;
      name: string;
      description: string;
    },
    {
      role_id: number;
      name: string;
      description: string;
    },
    {
      role_id: number;
      name: string;
      description: string;
    },
    {
      role_id: number;
      name: string;
      description: string;
    },
  ];
}
