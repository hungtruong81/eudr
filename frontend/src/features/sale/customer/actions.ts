import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { ICustomer, ICustomerData } from "./types";

export interface IGetCustomerParams extends CommonPaginationParams {
  status: string;
}
export const getCustomers = async (
  params: Partial<IGetCustomerParams>,
): Promise<ApiResponseList<ICustomer[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/customers/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createCustomer = async (data: ICustomerData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/customers/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCustomer = async (
  customer_code: string,
  data: ICustomerData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/customers/${customer_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCustomer = async (customer_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/customers/${customer_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCodeCustomer = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/customers/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
