import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IFactory, IFactoryData } from "./types";

export const getFactory = async (
  params: CommonPaginationParams,
): Promise<ApiResponseList<IFactory[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/factory/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createFactory = async (data: IFactoryData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/factory/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateFactory = async (
  factory_code: string,
  data: IFactoryData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/factory/${factory_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteFactory = async (factory_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/factory/${factory_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
