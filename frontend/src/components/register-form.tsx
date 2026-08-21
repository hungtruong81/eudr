"use client";

import { OTP_TYPE } from "@/constants/status";
import {
  getCompanyRegister,
  registerOTP,
  signUpApi,
  verifyOTP,
} from "@/lib/api";
import { useUser } from "@/providers/user-context";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  Button,
  Card,
  Checkbox,
  Form,
  Input,
  Radio,
  Select,
  Typography,
} from "antd";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useRouter } from "nextjs-toploader/app";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { getFirstAllowedRoute } from "./login-form";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

export interface ICompanyRegister {
  company_code: string;
  company_name: string;
  short_name: string;
  address: string;
}

type PhoneFormData = { phone: string };
type OtpFormData = { otp_code: string };
type RegisterFormData = {
  full_name: string;
  email: string;
  password: string;
  confirm_password: string;
  phone: string;
  account_type: "individual" | "company";
  register_type: string[];
  company_code?: string;
};

export default function RegisterForm() {
  const t = useTranslations("Register");
  const tc = useTranslations("Common");
  const searchParams = useSearchParams();
  const router = useRouter();
  const returnUrl = searchParams.get("returnUrl");
  const { fetchUserInfo, userInfo } = useUser();
  const [step, setStep] = useState<"phone" | "otp" | "register" | "terms">(
    "phone",
  );

  const [otpRequestId, setOtpRequestId] = useState<number | null>(null);
  const [phoneNumber, setPhoneNumber] = useState<string>("");
  const [countdown, setCountdown] = useState<number>(60);
  const [isResendDisabled, setIsResendDisabled] = useState<boolean>(true);
  const [isTermsAccepted, setIsTermsAccepted] = useState<boolean>(false);

  const [phoneForm] = Form.useForm<PhoneFormData>();
  const [otpForm] = Form.useForm<OtpFormData>();
  const [registerForm] = Form.useForm<RegisterFormData>();

  const accountType = Form.useWatch("account_type", registerForm);

  const { data: companyData } = useQuery({
    queryKey: ["company-register"],
    queryFn: getCompanyRegister,
  });

  useEffect(() => {
    if (userInfo) {
      const userPermissions = userInfo.permissions || [];
      const firstAllowed = getFirstAllowedRoute(userPermissions);
      if (firstAllowed) {
        router.push(returnUrl || firstAllowed);
      }
    }
  }, [userInfo, router, returnUrl]);

  useEffect(() => {
    let timer: NodeJS.Timeout | null = null;
    if (step === "otp" && countdown > 0) {
      timer = setInterval(() => {
        setCountdown((prev) => {
          if (prev <= 1) {
            setIsResendDisabled(false);
            clearInterval(timer!);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }
    return () => {
      if (timer) clearInterval(timer);
    };
  }, [step, countdown]);

  const requestOtpMutation = useMutation({
    mutationFn: (data: PhoneFormData) =>
      registerOTP(data.phone, OTP_TYPE.REIGSTER),
    onSuccess: (response) => {
      setOtpRequestId(response.data.otp_request_id);
      const currentPhone = phoneForm.getFieldValue("phone");
      setPhoneNumber(currentPhone);
      setStep("otp");
      otpForm.setFieldsValue({ otp_code: response.data.otp_code });
      setCountdown(60);
      setIsResendDisabled(true);
      toast.success(t("otp_sent"));
    },
    onError: handleApiError,
  });

  const verifyOtpMutation = useMutation({
    mutationFn: (data: OtpFormData) =>
      verifyOTP(
        otpRequestId || 0,
        phoneNumber,
        OTP_TYPE.REIGSTER,
        data.otp_code,
      ),
    onSuccess: (response) => {
      if (response.result === "success") {
        registerForm.setFieldsValue({ phone: phoneNumber });
        setStep("terms");
        toast.success(t("otp_verify_success"));
      } else {
        toast.error(t("otp_verify_failed"));
      }
    },
    onError: handleApiError,
  });

  const registerMutation = useMutation({
    mutationFn: (data: RegisterFormData) =>
      signUpApi(
        data.email,
        data.password,
        data.full_name,
        data.register_type,
        data.phone,
        otpRequestId || 0,
        OTP_TYPE.REIGSTER,
        data.company_code || "",
      ),
    onSuccess: async (response) => {
      if (response.result === "success") {
        document.cookie = `eudr_2025_access_token=${response.access_token}; path=/; max-age=604800; Secure; SameSite=Strict`;
        await fetchUserInfo();
      } else {
        toast.error(response.error?.description || t("failed"));
      }
    },
    onError: handleApiError,
  });

  const handleResendOtp = () => {
    if (!isResendDisabled) {
      requestOtpMutation.mutate({ phone: phoneNumber });
    }
  };

  if (userInfo) {
    return (
      <div className="flex justify-center items-center h-96">
        <p className="text-gray-500">{tc("redirecting")}</p>
      </div>
    );
  }

  return (
    <Card className="mx-auto max-w-lg mt-8 shadow-sm">
      <div className="mb-6 text-xl font-bold">
        {t("title")}
        <br />
        <Typography.Text type="secondary" style={{ fontWeight: "normal", fontSize: "small" }}>
          {t("subtitle")}
        </Typography.Text>
      </div>

      {/* BƯỚC 1: NHẬP SỐ ĐIỆN THOẠI */}
      {step === "phone" && (
        <Form
          form={phoneForm}
          layout="vertical"
          onFinish={(values) => requestOtpMutation.mutate(values)}>
          <Form.Item
            label={tc("phone_number")}
            name="phone"
            rules={[
              { required: true, message: t("enter_phone_error") },
              {
                pattern: /^((03|05|07|08|09)[0-9]{8})$/,
                message: t("invalid_phone_error"),
              },
            ]}>
            <Input placeholder={t("phone_placeholder")} size="large" />
          </Form.Item>
          <Button
            type="primary"
            htmlType="submit"
            size="large"
            className="w-full"
            loading={requestOtpMutation.isPending}>
            {requestOtpMutation.isPending ? tc("processing") : t("send_otp")}
          </Button>
        </Form>
      )}

      {/* BƯỚC 2: XÁC MINH OTP */}
      {step === "otp" && (
        <Form
          form={otpForm}
          layout="vertical"
          onFinish={(values) => verifyOtpMutation.mutate(values)}>
          <Form.Item
            label={t("otp_code")}
            name="otp_code"
            rules={[
              { required: true, message: t("enter_otp_error") },
              { len: 6, message: t("invalid_otp_error") },
            ]}
            className="text-center">
            {/* Sử dụng Input.OTP của Ant Design */}
            <Input.OTP length={6} size="large" />
          </Form.Item>

          <Button
            type="primary"
            htmlType="submit"
            size="large"
            className="w-full mb-4"
            loading={verifyOtpMutation.isPending}>
            {verifyOtpMutation.isPending ? tc("processing") : t("verify_otp")}
          </Button>

          <div className="text-center">
            <Button
              type="link"
              onClick={handleResendOtp}
              disabled={isResendDisabled || requestOtpMutation.isPending}>
              {t("resend_otp")}
            </Button>
            {isResendDisabled && (
              <p className="text-sm text-gray-500 mt-1">
                {t("resend_after_seconds", { seconds: countdown })}
              </p>
            )}
          </div>
        </Form>
      )}

      {/* BƯỚC 3: ĐIỀU KHOẢN */}
      {step === "terms" && (
        <div className="grid gap-4">
          <div className="grid gap-2">
            <label className="font-medium">{t("terms_title")}</label>
            <div
              className="h-fit max-h-40 overflow-y-auto border p-4 text-sm bg-gray-50 rounded-md"
              style={{ scrollbarWidth: "thin" }}>
              <p>{t("terms_title")}...</p>
            </div>
          </div>

          <Checkbox
            checked={isTermsAccepted}
            onChange={(e) => setIsTermsAccepted(e.target.checked)}>
            {t("terms_agreement")}
          </Checkbox>

          <Button
            type="primary"
            size="large"
            className="w-full mt-2"
            onClick={() => setStep("register")}
            disabled={!isTermsAccepted}>
            {tc("continue")}
          </Button>
        </div>
      )}

      {/* BƯỚC 4: THÔNG TIN ĐĂNG KÝ */}
      {step === "register" && (
        <Form
          form={registerForm}
          layout="vertical"
          onFinish={(values) => registerMutation.mutate(values)}
          initialValues={{ register_type: ["farmer"] }}>
          <div className="grid grid-cols-2 gap-4">
            <Form.Item
              label={tc("full_name")}
              name="full_name"
              rules={[{ required: true, message: t("enter_name_error") }]}>
              <Input placeholder={t("name_placeholder")} size="large" />
            </Form.Item>

            <Form.Item label={tc("phone_number")} name="phone">
              <Input placeholder={tc("phone_number")} disabled size="large" />
            </Form.Item>
          </div>

          <Form.Item
            label={tc("email")}
            name="email"
            rules={[
              { required: true, message: t("enter_email_error") },
              { type: "email", message: t("invalid_email_error") },
            ]}>
            <Input placeholder={t("email_placeholder")} size="large" />
          </Form.Item>

          <Form.Item
            label={t("password")}
            name="password"
            rules={[
              { required: true, message: t("enter_password_error") },
              { min: 6, message: t("password_min_length_error") },
            ]}>
            <Input.Password placeholder={t("password_placeholder")} size="large" />
          </Form.Item>

          <Form.Item
            label={t("confirm_password")}
            name="confirm_password"
            dependencies={["password"]}
            rules={[
              { required: true, message: t("enter_confirm_password_error") },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || getFieldValue("password") === value)
                    return Promise.resolve();
                  return Promise.reject(
                    new Error(t("mismatch_password_error")),
                  );
                },
              }),
            ]}>
            <Input.Password placeholder={t("confirm_password")} size="large" />
          </Form.Item>

          <Form.Item
            label={t("account_type")}
            name="account_type"
            rules={[{ required: true, message: t("select_account_type_error") }]}>
            <Select
              placeholder={t("account_type_placeholder")}
              size="large"
              className="w-full"
              options={[
                { label: t("individual"), value: "individual" },
                { label: t("company"), value: "company" },
              ]}
            />
          </Form.Item>

          {/* <Form.Item label="Công ty (Tùy chọn)" name="company_code">
            <Select
              placeholder="Chọn công ty trực thuộc"
              size="large"
              disabled={!companyData?.companies}
              allowClear>
              <Select.Option value="">
                <span className="text-muted-foreground italic">
                  -- Không thuộc công ty nào --
                </span>
              </Select.Option>
              {companyData?.companies?.map((company: ICompanyRegister) => (
                <Select.Option
                  key={company.company_code}
                  value={company.company_code}>
                  {company.company_name}
                </Select.Option>
              ))}
            </Select>
          </Form.Item> */}

          <Form.Item
            label={t("register_type")}
            name="register_type"
            rules={[
              {
                required: true,
                message: t("select_register_type_error"),
              },
            ]}>
            <Select
              mode="multiple"
              placeholder={t("register_type_placeholder")}
              size="large"
              maxTagCount={3}
              options={[
                { label: t("farmer"), value: "farmer" },
                { label: t("purchaser"), value: "purchaser" },
                { label: t("transport"), value: "transport" },
                { label: t("factory"), value: "factory" },
                { label: t("sales"), value: "sales" },
                ...(accountType === "company"
                  ? [{ label: t("company"), value: "company" }]
                  : []),
              ]}
            />
          </Form.Item>

          <Button
            type="primary"
            htmlType="submit"
            size="large"
            className="w-full mt-2"
            loading={registerMutation.isPending}>
            {registerMutation.isPending ? tc("processing") : t("title")}
          </Button>

          <div className="mt-4 text-center text-sm">
            {t("already_have_account")}{" "}
            <Link href="/login" className="underline">
              {t("login_link")}
            </Link>
          </div>
        </Form>
      )}
    </Card>
  );
}
