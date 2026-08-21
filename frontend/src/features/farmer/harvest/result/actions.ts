import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { IScheduleData } from "../plan/types";

export const createOrUpdateHarvest = async (data: {
  harvest_schedule_code: string;
  actual_yield: number;
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/harvest/result/`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateHarvestScheduleDate = async (data: IScheduleData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/harvest/result/schedule/`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};
