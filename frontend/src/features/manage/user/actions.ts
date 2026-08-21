import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import {
  IUpdateUserCompanyData,
  IUserCompany,
  IUserCompanyData,
} from "./types";

export interface IGetUserCompanyParams extends CommonPaginationParams {
  register_type: string;
  company_id: string;
}
export const getUserCompany = async (
  params: Partial<IGetUserCompanyParams>,
): Promise<ApiResponseList<IUserCompany[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-member/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createCompanyMember = async (data: IUserCompanyData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-member/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCompanyMember = async (
  user_code: string,
  data: IUpdateUserCompanyData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-member/${user_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCompanyMember = async (user_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company-member/${user_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deActiveUser = async (user_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/users/deactivate/${user_code}`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
