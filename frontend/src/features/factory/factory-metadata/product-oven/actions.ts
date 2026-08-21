import { generateBaseApiUrl } from "@/lib/utils";
import axiosInstance from "@/lib/axios-instance";
import { ApiResponseList } from "@/types/api";
import { IProductionOven } from "./types";
import { CommonPaginationParams } from "@/types/api";

interface IGetProductionOvensParams extends CommonPaginationParams {
  factory_id?: number;
  status?: string; //available,in_use,cleaning,all
}

export const getProductionOvens = async (
  params: IGetProductionOvensParams,
): Promise<ApiResponseList<IProductionOven[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductionOven = async (data: IProductionOven) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductionOvenByCode = async (oven_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/${oven_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductionOven = async (
  oven_code: string,
  data: IProductionOven,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/${oven_code}`;
    const response = await axiosInstance.patch(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductionOven = async (oven_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/${oven_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateProductionOvenCode = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-oven/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
