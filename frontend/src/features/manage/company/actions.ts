import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { ICompany, ICompanyData } from "./types";

export interface IGetCompanyParams extends CommonPaginationParams {
  status?: "all" | "active" | "inactive";
}
export const getCompanys = async (
  params: IGetCompanyParams,
): Promise<ApiResponseList<ICompany[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/company/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createCompany = async (data: ICompanyData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCompany = async (
  company_code: string,
  data: ICompanyData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company/${company_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCompany = async (company_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/company/${company_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
