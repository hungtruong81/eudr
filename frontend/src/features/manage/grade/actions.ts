import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  IGrade,
  IGradeData,
  IGradePriceCurrent,
  IGradePriceData,
  IGradePriceHistory,
} from "./type";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";

export interface IGetGradeParams extends CommonPaginationParams {}
export interface IGetGradePriceHistoryParams extends CommonPaginationParams {
  effective_from?: string;
  effective_to?: string;
  grade_code: string;
}

export const getGrade = async (
  params: IGetGradeParams,
): Promise<ApiResponseList<IGrade[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getGradeByCode = async (
  grade_code: string,
): Promise<ApiResponse<IGrade>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/${grade_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCodeGrade = async (): Promise<
  ApiResponse<{ grade_code: string }>
> => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createGrade = async (data: IGradeData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateGrade = async (grade_code: string, data: IGradeData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/${grade_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteGrade = async (grade_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/${grade_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createGradePrice = async (
  grade_code: string,
  data: IGradePriceData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/grade/${grade_code}/prices/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getGradePriceCurrent = async (
  grade_code: string,
): Promise<ApiResponse<IGradePriceCurrent>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/grade/${grade_code}/prices/current/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getGradePriceHistory = async (
  params: IGetGradePriceHistoryParams,
): Promise<ApiResponseList<IGradePriceHistory[]>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/grade/${params.grade_code}/prices/history/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
