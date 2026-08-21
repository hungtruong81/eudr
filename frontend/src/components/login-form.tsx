"use client";

import { cn } from "@/components/lib/utils";
import { useUser } from "@/providers/user-context";
import { useMutation } from "@tanstack/react-query";
import { Button, Card, Form, Input } from "antd";
import Image from "next/image";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useRouter } from "nextjs-toploader/app";
import { toast } from "sonner";
import { hasAnyPermission, MenuItem } from "./app-sidebar";
import { useEffect } from "react";
import { useTranslations } from "next-intl";

import { getCookie } from "cookies-next";
import { menuConfig } from "@/config/menu-config";
import { handleApiError } from "@/lib/api-error";

type FormValues = {
  phone: string;
  password: string;
};

export const getFirstAllowedRoute = (userPermissions: string[]) => {
  const findRoute = (items: MenuItem[]): string | null => {
    for (const item of items) {
      const hasItemPermission = hasAnyPermission(
        userPermissions,
        item.requiredPermissions,
      );
      if (!hasItemPermission) continue;

      if (item.items && item.items.length > 0) {
        const sub = findRoute(item.items);
        if (sub) return sub;
      } else if (item.url && item.url !== "#") {
        return item.url;
      }
    }
    return null;
  };

  return findRoute(menuConfig());
};

export function LoginForm({
  className,
  ...props
}: React.ComponentProps<"div">) {
  const t = useTranslations("Login");
  const tc = useTranslations("Common");
  const router = useRouter();
  const searchParams = useSearchParams();
  const returnUrl = searchParams.get("returnUrl");
  const { doLogin, userInfo } = useUser();
  const accessToken = getCookie("eudr_2025_access_token");

  const [form] = Form.useForm<FormValues>();

  useEffect(() => {
    if (!accessToken) return localStorage.clear();

    if (userInfo) {
      const firstAllowed = getFirstAllowedRoute(userInfo.permissions);
      if (returnUrl) {
        router.replace(returnUrl);
      } else {
        router.replace(firstAllowed || `/connection/`);
      }
    }
  }, [userInfo, returnUrl, router, accessToken]);

  const loginMutation = useMutation({
    mutationFn: async (data: FormValues) => {
      return await doLogin(data.phone.trim(), data.password.trim(), "");
    },
    onSuccess: (result) => {
      if (result.result === "success") {
        const userPermissions = result?.user?.permissions ?? [];
        if (returnUrl) {
          router.push(returnUrl);
          return;
        }
        const firstAllowed = getFirstAllowedRoute(userPermissions);
        if (firstAllowed) {
          router.push(firstAllowed);
        }
      } else {
        toast.error(result.error || t("failed"));
      }
    },
    onError: handleApiError,
  });

  const onFinish = (values: FormValues) => {
    loginMutation.mutate(values);
  };

  if (userInfo) {
    return (
      <div className="flex justify-center items-center h-96">
        <p className="text-gray-500">{tc("redirecting")}</p>
      </div>
    );
  }

  return (
    <div className={cn("flex flex-col gap-6", className)} {...props}>
      <Card className="overflow-hidden" styles={{ body: { padding: 0 } }}>
        <div className="grid md:grid-cols-2">
          <Form
            form={form}
            layout="vertical"
            onFinish={onFinish}
            style={{ padding: "1rem" }}>
            <div className="flex flex-col items-center text-center">
              <h1 className="text-2xl font-bold">{t("welcome_back")}</h1>
              <p className="text-balance">{t("subtitle")}</p>
            </div>

            {loginMutation.isError && (
              <div className="text-destructive text-sm text-center">
                {t("error_retry")}
              </div>
            )}

            <div className="grid gap-1">
              <Form.Item
                label={t("phone_email")}
                name="phone"
                rules={[
                  {
                    required: true,
                    message: t("enter_phone_email_error"),
                  },
                  {
                    validator: (_, value) => {
                      if (!value) return Promise.resolve();

                      const phoneRegex = /^((03|05|07|08|09)[0-9]{8})$/;
                      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                      if (phoneRegex.test(value) || emailRegex.test(value)) {
                        return Promise.resolve();
                      }

                      return Promise.reject(
                        new Error(t("invalid_phone_email_error")),
                      );
                    },
                  },
                ]}
                className="mb-0">
                <Input
                  placeholder={t("phone_email_placeholder")}
                  size="large"
                />
              </Form.Item>
            </div>

            <div className="grid gap-1">
              <Form.Item
                label={t("password")}
                name="password"
                rules={[{ required: true, message: t("enter_password_error") }]}
                className="mb-0">
                <Input.Password placeholder={t("password_placeholder")} size="large" />
              </Form.Item>
            </div>

            <Button
              type="primary"
              htmlType="submit"
              size="large"
              className="w-full"
              loading={loginMutation.isPending}>
              {t("submit")}
            </Button>

            <div className="text-center text-sm">
              {t("no_account")}{" "}
              <Link href="/signup" className="underline underline-offset-4">
                {t("register_link")}
              </Link>
            </div>

            <div className="text-center text-xs text-balance mt-4">
              {t("agreement")}
            </div>
          </Form>

          <div className="bg-muted relative hidden md:block min-h-[400px]">
            <Image
              src="/thumb-scrubber.jpg"
              alt="Hình ảnh"
              fill
              className="object-cover"
            />
          </div>
        </div>
      </Card>
    </div>
  );
}
