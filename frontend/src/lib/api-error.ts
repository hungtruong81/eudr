import { message } from "antd";

export function handleApiError(error: unknown) {
  const axiosError = error as any;

  const errorMessage =
    axiosError?.response?.data?.error?.description ||
    axiosError?.response?.data?.message ||
    axiosError?.message ||
    axiosError?.error?.description ||
    "Có lỗi xảy ra";

  const status = axiosError?.response?.status;

  if (status === 403) {
    message.error(errorMessage);
    return;
  }

  //   if (status === 401) {
  //     logout();
  //     return;
  //   }

  if (status && status >= 500) {
    message.error("Hệ thống đang bận, vui lòng thử lại sau");
    return;
  }

  message.error(errorMessage);
}
