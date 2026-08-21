import axiosInstance from "@/lib/axios-instance";
import { IIssue, IIssueData } from "./types";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";

export interface IGetIssuesParams extends CommonPaginationParams {
  search?: string;
  status?: "draft" | "issued" | "cancelled" | "all";
  issue_date_from?: string;
  issue_date_to?: string;
  sale_order_id?: string;
}

export const getIssues = async (
  params: IGetIssuesParams,
): Promise<ApiResponseList<IIssue>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createIssue = async (data: IIssueData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getIssueByCode = async (
  sale_issue_code: string,
): Promise<{ issue: IIssue }> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/${sale_issue_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateIssue = async (
  sale_issue_code: string,
  data: IIssueData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/${sale_issue_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteIssue = async (sale_issue_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/${sale_issue_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelIssue = async (sale_issue_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/sales/issues/${sale_issue_code}/cancel`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const confirmIssue = async (sale_issue_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/sales/issues/${sale_issue_code}/confirm`;
    const response = await axiosInstance.post(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCode = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/issues/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
