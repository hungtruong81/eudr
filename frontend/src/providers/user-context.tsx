/* eslint-disable react-hooks/exhaustive-deps */
"use client";

import { UserConfig } from "@/lib/types";
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";

import { getUserInfoApi, signInApi } from "@/lib/api";
import { getDictionary } from "@/lib/dictionaries";
import { formatUser } from "@/lib/utils";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";

// Constants
const USER_STORAGE_KEY = "eudr_2025-user";

interface LoginResult {
  result: "success" | "fail";
  error?: string;
  user?: UserConfig | null;
}

interface UserContextValue {
  userInfo: UserConfig | null;
  doLogout: () => void;
  doLogin: (
    email: string,
    password: string,
    captcha: string,
  ) => Promise<LoginResult>;
  fetchUserInfo: () => Promise<void>;
  isLoading: boolean;
  isErrorTxt: string;
  isShowLogin: boolean;
  isShowRegister: boolean;
  isShowLostPassword: boolean;
  setIsShowLogin: (value: boolean) => void;
  setIsShowRegister: (value: boolean) => void;
  setIsShowLostPassword: (value: boolean) => void;

  isAdmin: boolean;
  isWorker: boolean;
  isFarmer: boolean;
  isTrader: boolean;
  isPurchaser: boolean;
  isCompany: boolean;
  isInspector: boolean;
}

const UserContext = createContext<UserContextValue>({
  userInfo: null,
  doLogout: () => {},
  doLogin: () => Promise.resolve({ result: "fail", error: "Not implemented" }),
  fetchUserInfo: () => Promise.resolve(),
  isErrorTxt: "",
  isLoading: false,
  isShowLogin: false,
  isShowRegister: false,
  isShowLostPassword: false,
  setIsShowLogin: () => {},
  setIsShowRegister: () => {},
  setIsShowLostPassword: () => {},

  isAdmin: false,
  isWorker: false,
  isFarmer: false,
  isTrader: false,
  isPurchaser: false,
  isCompany: false,
  isInspector: false,
});

export const useUser = () => {
  return useContext(UserContext);
};

interface Props {
  children: React.ReactNode;
}

export const UserProvider = ({ children }: Props) => {
  const queryClient = useQueryClient();
  const [userInfo, setUserInfo] = useState<UserConfig | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const [isShowLogin, setIsShowLogin] = useState(false);
  const [isShowRegister, setIsShowRegister] = useState(false);
  const [isShowLostPassword, setIsShowLostPassword] = useState(false);
  const [isErrorTxt, setIsErrorTxt] = useState("");

  const dict = getDictionary("vn");

  // Load cart from localStorage when component mounts

  // Save cart to localStorage whenever it changes
  useEffect(() => {
    if (!isLoading) {
      try {
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(userInfo));
      } catch (error) {
        console.error("Failed to save cart to localStorage:", error);
      }
    }
  }, [userInfo, isLoading]);

  const fetchUserInfo = async () => {
    try {
      const response = await getUserInfoApi();
      if (response.result !== "success") {
        throw new Error(response.errorMessage);
      }
      const userData = formatUser(response.user);
      setUserInfo(userData);
    } catch (error) {
      console.error("Failed to fetch user info:", error);
      setUserInfo(null);
    }
  };
  // Fetch user info if access token exists in cookies

  const doLogout = useCallback(() => {
    setUserInfo(null);
    localStorage.setItem(USER_STORAGE_KEY, "");
    queryClient.clear();
    // clear cookie
    document.cookie =
      "eudr_2025_access_token=; path=/; max-age=0; Secure; SameSite=Strict";
  }, []);

  const doLogin = useCallback(
    async (
      email: string,
      password: string,
      captcha: string,
    ): Promise<LoginResult> => {
      if (email.trim().length < 3 || password.trim().length < 3) {
        toast.error("Please provide the email/password");
        setIsErrorTxt("Please provide the email/password");
        setIsLoading(false);
        return { result: "fail", error: "Please provide the email/password" };
      }
      setIsLoading(true);
      setIsErrorTxt("");
      try {
        const data = await signInApi(email, password, captcha);

        if (data.result === "success") {
          toast.success("Đăng nhập thành công");
          document.cookie = `eudr_2025_access_token=${data.access_token}; path=/; max-age=604800; Secure; SameSite=Strict`;

          const response = await getUserInfoApi();
          let user: UserConfig | null = null;
          if (response.result === "success") {
            user = formatUser(response.user);
            setUserInfo(user);
          }

          return { result: "success", user };
        } else {
          const errorMsg = data.error?.description || "Login failed";
          setIsErrorTxt(errorMsg);
          return { result: "fail", error: errorMsg };
        }
      } catch (error: any) {
        const errorMsg = error?.message || "Failed to login";
        setIsErrorTxt(errorMsg);
        toast.error(errorMsg);
        return { result: "fail", error: errorMsg };
      } finally {
        setIsLoading(false);
      }
    },
    [],
  );

  useEffect(() => {
    const getCookie = (name: string): string | null => {
      const match = document.cookie.match(
        new RegExp("(^| )" + name + "=([^;]+)"),
      );
      return match ? decodeURIComponent(match[2]) : null;
    };

    const accessToken = getCookie("eudr_2025_access_token");
    if (accessToken) {
      fetchUserInfo();
    }
  }, [doLogin, doLogout]);

  useEffect(() => {
    const loadUserFromStorage = () => {
      try {
        const storedUser = localStorage.getItem(USER_STORAGE_KEY);
        if (storedUser) {
          setUserInfo(JSON.parse(storedUser));
        }
      } catch (error) {
        console.error("Failed to load cart from localStorage:", error);
      } finally {
        setIsLoading(false);
      }
    };

    loadUserFromStorage();
  }, [doLogin, doLogout]);

  const contextValue = useMemo(
    () => ({
      userInfo,
      doLogout,
      doLogin,
      fetchUserInfo,
      isLoading,
      isErrorTxt,
      isShowLogin,
      isShowRegister,
      isShowLostPassword,
      setIsShowLogin,
      setIsShowRegister,
      setIsShowLostPassword,

      isAdmin:
        userInfo?.user_role?.some((role) => role.name === "admin") || false,
      isWorker:
        userInfo?.user_role?.some((role) => role.name === "worker") || false,
      isFarmer:
        userInfo?.user_role?.some((role) => role.name === "farmer") || false,
      isTrader:
        userInfo?.user_role?.some((role) => role.name === "trader") || false,
      isPurchaser:
        userInfo?.user_role?.some((role) => role.name === "purchaser") || false,
      isCompany:
        userInfo?.user_role?.some((role) => role.name === "company") || false,
      isInspector:
        userInfo?.user_role?.some((role) => role.name === "inspector") || false,
    }),
    [
      userInfo,
      doLogout,
      doLogin,
      fetchUserInfo,
      isLoading,
      isErrorTxt,
      isShowLogin,
      isShowRegister,
      isShowLostPassword,
      setIsShowLogin,
      setIsShowRegister,
      setIsShowLostPassword,
    ],
  );

  return (
    <UserContext.Provider value={contextValue}>{children}</UserContext.Provider>
  );
};
