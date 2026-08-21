import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IProductTank, IProductTankData } from "./types";
import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";

export interface IGetProductTankParams extends CommonPaginationParams {
  factory_id?: number;
  product_type?: string;
}

export const getProductTank = async (
  params: IGetProductTankParams,
): Promise<ApiResponseList<IProductTank[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-tank/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductTank = async (data: IProductTankData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-tank/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductTank = async (
  product_tank_code: string,
  data: IProductTankData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-tank/${product_tank_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductTank = async (product_tank_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-tank/${product_tank_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const historyProductTank = async (
  product_tank_code: string,
  params: CommonPaginationParams,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/product-tank/${product_tank_code}/history/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
