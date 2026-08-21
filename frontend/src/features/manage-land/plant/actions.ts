import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IPlant } from "./types";

export interface IGetPlantParams extends CommonPaginationParams {
  plot_code?: string;
}

export const getPlants = async (
  params: Partial<IGetPlantParams>,
): Promise<ApiResponseList<IPlant[]>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPlantById = async (
  id: string,
): Promise<ApiResponseList<IPlant>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/" + id;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createPlant = async (params: Partial<IPlant>) => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/";
    const response = await axiosInstance.post(url, params);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updatePlant = async (params: Partial<IPlant>) => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/" + params.plant_code;
    const response = await axiosInstance.put(url, params);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getCropTypes = async (): Promise<{
  data: { crop_type_name: string }[];
}> => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/crop-type/";
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deletePlant = async (plant_code: string) => {
  try {
    const url = generateBaseApiUrl() + "/v1/plant/" + plant_code;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
