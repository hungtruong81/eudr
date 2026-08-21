import { formatCourse, generateBaseApiUrl } from "@/lib/utils";
import axiosInstance from "@/lib/axios-instance";
import axios from "axios";
import qs from "qs";
import { AuthResponse, IRequestOTP } from "@/types/auth";
import { ApiResponseProvince, ApiResponseZone } from "@/types/province";
import { SettingResponse } from "@/types/setting";
import { ICompanyRegister } from "@/components/register-form";
import { IFile } from "@/types/file";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IContract } from "./types";

export async function registerOTP(phone: string, purpose: string) {
  const url = generateBaseApiUrl() + "/v1/auth/request-otp/";
  const fields = {
    phone,
    purpose,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });
    return response.data as IRequestOTP;
  } catch (error: any) {
    throw error.response?.data || error;
  }
}

export async function verifyOTP(
  otp_request_id: number,
  phone: string,
  purpose: string,
  otp_code: string,
) {
  const url = generateBaseApiUrl() + "/v1/auth/verify-otp/";
  const fields = {
    otp_request_id,
    phone,
    purpose,
    otp_code,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });
    return response.data as { result: string };
  } catch (error: any) {
    throw error.response?.data || error;
  }
}

export async function signInApi(
  phone: string,
  password: string,
  captcha: string,
): Promise<AuthResponse> {
  const url = generateBaseApiUrl() + "/v1/auth/login/";

  const fields = {
    phone,
    password,
    captcha,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    return response.data as AuthResponse;
  } catch (error: any) {
    console.error("signInApi Error:", error);
    throw error.response?.data || error;
  }
}

export async function getSetting() {
  const url = generateBaseApiUrl() + "/v1/general/settings";

  try {
    const response = await axiosInstance.get(url);
    return response.data as SettingResponse;
  } catch (error: any) {
    console.error("getSetting Error:", error);
    throw error.response?.data || error;
  }
}

export async function updateSetting(
  settings: {
    setting_code: string;
    comment: string;
    value: string;
  }[],
) {
  const url = generateBaseApiUrl() + "/v1/general/settings";

  try {
    const response = await axiosInstance.post(url, { settings });
    return response.data;
  } catch (error: any) {
    console.error("updateSetting Error:", error);
    throw error.response?.data || error;
  }
}

export async function signUpApi(
  email: string,
  password: string,
  full_name: string,
  register_type: string[],
  phone: string,
  otp_request_id: number,
  purpose: string,
  company_code: string,
): Promise<AuthResponse> {
  const url = generateBaseApiUrl() + "/v1/auth/register/";

  const fields = {
    otp_request_id,
    email,
    password,
    full_name,
    register_type,
    phone,
    purpose,
    company_code,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });
    return response.data as AuthResponse;
  } catch (error: any) {
    console.error("signUpApi Error:", error);
    throw error.response?.data || error;
  }
}

export async function getProvince(): Promise<ApiResponseProvince> {
  const url = generateBaseApiUrl() + "/v1/general/province";
  try {
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    console.log(error);
    throw error;
  }
}

export async function getZone(): Promise<ApiResponseZone> {
  const url = generateBaseApiUrl() + "/v1/general/zone";
  try {
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    console.log(error);
    throw error;
  }
}

export async function signInSocialApi(
  token: string,
  state: string,
  social: string,
) {
  const url = generateBaseApiUrl() + "/v1/auth/login/" + social + "/";

  const fields = {
    token,
    state,
    social,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });
    const data = response.data;

    return data;
  } catch (error) {
    console.error("signInApi Error:", error);
    throw error;
  }
}

export async function getUserInfoApi() {
  // version default v1
  const url = generateBaseApiUrl() + `/v1/auth/info/`;
  const query = qs.stringify({});

  try {
    if (!url) {
      throw new Error("Invalid URL");
    }
    const response = await axiosInstance.get(`${url}?${query}`, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    const data = response.data;

    return data;
  } catch (error) {
    if (axios.isAxiosError(error) && error.response) {
      return error.response.data;
    }
    throw error;
    // throw error;
  }
}
export async function getGeneralDataApi() {
  // version default v1
  const url = generateBaseApiUrl() + `/v1/general/data`;
  const query = qs.stringify({});

  try {
    if (!url) {
      throw new Error("Invalid URL");
    }
    const response = await axiosInstance.get(`${url}?${query}`, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    const data = response.data;

    return data;
  } catch (error) {
    if (axios.isAxiosError(error) && error.response) {
      return error.response.data;
    }
    throw error;
    // throw error;
  }
}

export async function getListCoursesApi({
  page = 1,
  search = "",
  from,
  to,
  limit,
  ids,
  order_by = "name",
  order_type = "asc",
}: {
  page?: number;
  search?: string;
  from?: string;
  to?: string;
  limit?: number;
  ids?: string[];
  order_by?: string;
  order_type?: string;
}) {
  const url = generateBaseApiUrl() + `/v1/courses/`;

  const fields = {
    page,
    search,
    from,
    to,
    limit,
    ids,
    order_by,
    order_type,
  };
  const query = qs.stringify(fields);

  try {
    const response = await axiosInstance.get(`${url}?${query}`, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    const data = response.data;

    return data;
  } catch (error) {
    console.error("getListCoursesApi Error:", error);
    throw error;
  }
}

export async function getCourseBySlugApi({ slug }: { slug: string }) {
  const url = generateBaseApiUrl() + `/v1/courses/${slug}`;

  const fields = {};
  const query = qs.stringify(fields);

  try {
    const response = await axiosInstance.get(`${url}?${query}`, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    const data = response.data.data;

    const course = formatCourse(data);
    if (!course) {
      throw new Error("Course not found");
    }

    return course;
  } catch (error) {
    console.error("getCourseByIdApi Error:", error);
    throw error;
  }
}

export const generateQr = async (params: {
  code: string;
  type: string;
}): Promise<{ qr_code: string }> => {
  const url = generateBaseApiUrl() + `/v1/general/generate-qr`;

  try {
    const response = await axiosInstance.get(url, { params });

    return response.data;
  } catch (error) {
    console.error("generateQr Error:", error);
    throw error;
  }
};

export const getCompanyRegister = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/general/company`;
    const response = await axiosInstance.get(url);

    return response.data as { result: string; companies: ICompanyRegister[] };
  } catch (error) {
    throw error;
  }
};

export async function upgradeAccountApi(
  add_roles: string[],
  remove_roles: string[],
) {
  const url = generateBaseApiUrl() + "/v1/users/upgrade/";

  const fields = {
    add_roles,
    remove_roles,
  };

  try {
    const response = await axiosInstance.post(url, fields, {
      headers: {
        "Content-Type": "application/json",
      },
    });

    return response.data;
  } catch (error: any) {
    console.error("upgradeAccountApi Error:", error);
    throw error.response?.data || error;
  }
}

export const uploadFile = async (
  form_data: FormData,
): Promise<{
  data: {
    file: IFile;
    detection: { coordinates: { x: number; y: number }[] };
  };
}> => {
  const url = generateBaseApiUrl() + "/v1/file/";

  try {
    const response = await axiosInstance.post(url, form_data);

    return response.data;
  } catch (error) {
    console.log(error);
    throw error;
  }
};

export interface IGetContractParams extends CommonPaginationParams {
  transaction_ticket_type?: "sale" | "purchase";
  status?: string;
  sales_source?: string;
}

export const getContracts = async (
  params: Partial<IGetContractParams>,
): Promise<ApiResponseList<IContract[]>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transaction-ticket/?transaction_ticket_type=sale&status=all`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
