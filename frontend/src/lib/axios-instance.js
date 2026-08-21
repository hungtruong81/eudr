// https://medium.com/@iamshahrukhkhan07/how-to-create-http-middleware-with-axios-in-react-next-js-70966ddf7865
import axios from "axios";
import { getCookie } from "cookies-next";

const axiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_URL_API,
  timeout: 20000,
  withCredentials:true
});

axiosInstance.interceptors.request.use(
  (config) => {
    // const accessToken = localStorage.getItem("web_accessToken");
    // Get accessToken from cookie
    /* const accessToken = document.cookie.replace(
      /(?:(?:^|.*;\s*)the_creator_access_token\s*\=\s*([^;]*).*$)|^.*$/,
      "$1",
    ); */
    const accessToken = getCookie("eudr_2025_access_token");

    if (accessToken) {
      config.headers["Authorization"] = `Bearer ${accessToken}`;
    }
    config.headers["Cache-Control"] = "no-cache";
    config.headers["Pragma"] = "no-cache";
    config.headers["Expires"] = "0";
    // config.headers['Content-Type'] = 'application/json';
    return config;
  },
  (error) => Promise.reject(error),
);

axiosInstance.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    if (error.response && error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      /* const refreshToken = localStorage.getItem('refreshToken');
      try {
        const { data } = await axiosInstance.post('/auth/refresh-token', { token: refreshToken });
        localStorage.setItem("web_accessToken", data.accessToken);
        axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${data.accessToken}`;
        return axiosInstance(originalRequest);
      } catch (refreshError) {
        // Handle token refresh error (e.g., redirect to login)
      } */
    }
    return Promise.reject(error);
  },
);
export default axiosInstance;
