import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import {
  ICompanyGroup,
  ICompanyGroupByCode,
  ICompanyGroupData,
  IGroupMember,
  IPermission,
  IAssignMemberToGroup,
  ISetGroupPermission,
} from "./types";

export interface IGetCompanyGroupParams extends CommonPaginationParams {
  status: string; //all | active | inactive
  company_id: number;
}
export const getCompanyGroup = async (
  params: IGetCompanyGroupParams,
): Promise<ApiResponseList<ICompanyGroup[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-group/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getCompanyGroupDetail = async (
  company_group_code: string,
): Promise<ApiResponse<ICompanyGroupByCode>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/company-group/${company_group_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createCompanyGroup = async (data: ICompanyGroupData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-group/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCompanyGroup = async (
  company_group_code: string,
  data: ICompanyGroupData,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/company-group/${company_group_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCompanyGroup = async (company_group_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/company-group/${company_group_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPermissions = async (): Promise<ApiResponse<IPermission[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/users/permission/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export interface IGetMemberParams extends CommonPaginationParams {
  company_group_code: string;
  company_id: number;
}
export const getMembers = async (
  params: IGetMemberParams,
): Promise<ApiResponseList<IGroupMember[]>> => {
  console.log(params);
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/company-group/${params.company_group_code}/members/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// Aliases for matching provided sample
export const getGroupMember = getMembers;

export interface IGetListUserParams extends CommonPaginationParams {
  search?: string;
  register_type?: string;
  company_id?: string;
}
export const getListUserCompany = async (
  params: IGetListUserParams,
): Promise<ApiResponseList<IGroupMember[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-member/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const assignMembers = async (
  company_group_code: string,
  data: IAssignMemberToGroup,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/company-group/${company_group_code}/assign-members/`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const AssignMemberToGroup = assignMembers;

export const setGroupPermissions = async (
  company_group_code: string,
  data: ISetGroupPermission,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/company-group/${company_group_code}/set-permissions/`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const setGroupPermission = setGroupPermissions;
