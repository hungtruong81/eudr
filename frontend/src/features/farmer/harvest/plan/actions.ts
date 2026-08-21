import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import {
  IHarvestPlan,
  IHarvestPlanData,
  ISchedule,
  IScheduleById,
  IScheduleData,
} from "./types";

export interface IGetPlanParams extends CommonPaginationParams {
  harvest_start_date?: string;
  harvest_end_date?: string;
  tapping_regime?: "D2" | "D3" | "D4" | "D5" | "D6";
  contract_code?: string;
}

export interface IGetSchedulesParams extends CommonPaginationParams {
  harvest_plan_code?: string;
  pickup_date?: string;
}

export const getPlan = async (
  params: Partial<IGetPlanParams>,
): Promise<ApiResponseList<IHarvestPlan[]>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/plan/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPlanById = async (
  harvest_plan_code: string,
): Promise<ApiResponseList<IHarvestPlan>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/plan/" + harvest_plan_code;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createPlan = async (data: IHarvestPlanData) => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/plan/";
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updatePlan = async (
  data: IHarvestPlanData,
  harvest_plan_code: string,
) => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/plan/" + harvest_plan_code;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deletePlan = async (harvest_plan_code: string) => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/plan/" + harvest_plan_code;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getSchedules = async (
  params: IGetSchedulesParams,
): Promise<ApiResponseList<ISchedule[]>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/schedule/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getScheduleById = async (
  harvest_schedule_code: string,
): Promise<ApiResponseList<IScheduleById>> => {
  try {
    const url =
      generateBaseApiUrl() + "/v1/harvest/schedule/" + harvest_schedule_code;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createSchedule = async (data: IScheduleData) => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/schedule/";
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateSchedule = async (data: IScheduleData) => {
  try {
    const url = generateBaseApiUrl() + "/v1/harvest/schedule/";
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};
