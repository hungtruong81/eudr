import { generateBaseApiUrl } from "@/lib/utils";
import { IPriceData } from "./types";
import axiosInstance from "@/lib/axios-instance";
import { CommonPaginationParams } from "@/types/api";

export interface IGetPriceParams extends CommonPaginationParams {}

export const createPrice = async (data: IPriceData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/price/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPrices = async (params: IGetPriceParams) => {
  try {
    const url = generateBaseApiUrl() + `/v1/price/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updatePrice = async (price_code: string, data: IPriceData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/price/${price_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deletePrice = async (price_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/price/${price_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
