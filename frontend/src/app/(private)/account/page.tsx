/* eslint-disable @next/next/no-img-element */
"use client";

import { useQuery, useMutation } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  Camera,
  Download,
  Mail,
  Phone,
  QrCode,
  Save,
  User,
  Building2,
  Tractor,
  ShoppingCart,
  Truck,
  Factory,
  Briefcase,
} from "lucide-react";
import { useEffect, useState, useMemo } from "react";

import {
  Avatar,
  Avatar as AntdAvatar,
  Button,
  Card,
  Checkbox,
  Divider,
  Input,
  Modal,
  Space,
  Tabs,
  Tag,
  Typography,
  Row,
  Col,
} from "antd";
import { useTranslations } from "next-intl";

import {
  REGISTER_TYPE,
  REGISTER_TYPE_LABEL,
  functionalities,
} from "@/constants/register-types";
import { generateQr, upgradeAccountApi } from "@/lib/api";
import { useUser } from "@/providers/user-context";
import UserRolesBadge from "@/components/shared/user-roles";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";

const { Title, Text } = Typography;

export default function AccountManagement() {
  const t = useTranslations("Account");
  const tc = useTranslations("Common");
  const tr = useTranslations("Register");
  const { userInfo } = useUser();

  const [activeTab, setActiveTab] = useState("profile");
  const [nameParts, setNameParts] = useState({ firstName: "", lastName: "" });

  const upgradedRoles = useMemo(() => {
    const roles: REGISTER_TYPE[] = [];
    if (userInfo?.user_role) {
      if (Array.isArray(userInfo.user_role)) {
        userInfo.user_role.forEach((role: any) => {
          const roleName = typeof role === "string" ? role : role?.name;
          if (roleName) roles.push(roleName as REGISTER_TYPE);
        });
      } else if (
        typeof userInfo.user_role === "string" &&
        (userInfo.user_role as string).includes(",")
      ) {
        (userInfo.user_role as string)
          .split(",")
          .forEach((r) => roles.push(r.trim() as REGISTER_TYPE));
      } else if (typeof userInfo.user_role === "string") {
        roles.push(userInfo.user_role as unknown as REGISTER_TYPE);
      }
    }
    if (Array.isArray((userInfo as any)?.roles)) {
      (userInfo as any).roles.forEach((r: any) =>
        roles.push(r as REGISTER_TYPE),
      );
    }
    return Array.from(new Set(roles));
  }, [userInfo]);

  const [selectedModules, setSelectedModules] = useState<REGISTER_TYPE[]>([]);

  useEffect(() => {
    setSelectedModules(upgradedRoles);
  }, [upgradedRoles]);

  const toggleModule = (type: REGISTER_TYPE) => {
    if (upgradedRoles.includes(type)) return;
    setSelectedModules((prev) =>
      prev.includes(type) ? prev.filter((m) => m !== type) : [...prev, type],
    );
  };

  useEffect(() => {
    if (userInfo?.fullName) {
      const parts = userInfo.fullName.trim().split(" ");
      if (parts.length === 1) {
        setNameParts({ firstName: parts[0], lastName: "" });
      } else {
        const firstName = parts.pop() || "";
        const lastName = parts.join(" ");
        setNameParts({ firstName, lastName });
      }
    }
  }, [userInfo]);

  const upgradeMutation = useMutation({
    mutationFn: (data: { add_roles: string[]; remove_roles: string[] }) =>
      upgradeAccountApi(data.add_roles, data.remove_roles),
    onSuccess: () => {
      toast.success(t("upgrade_success"));
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    },
    onError: (error: any) => {
      toast.error(error?.message || t("upgrade_fail"));
    },
  });

  const handleUpgradeAccount = () => {
    const add_roles = selectedModules.filter((m) => !upgradedRoles.includes(m));
    const remove_roles: string[] = [];

    if (add_roles.length === 0 && remove_roles.length === 0) {
      toast.info(t("select_package_info"));
      return;
    }

    upgradeMutation.mutate({ add_roles, remove_roles });
  };

  const { data: generalData } = useQuery({
    queryKey: ["generalData"],
    queryFn: () =>
      generateQr({
        code: "HD20251201001",
        type: "transaction_ticket",
      }),
    refetchOnWindowFocus: false,
    enabled: !!userInfo?.user_id,
  });

  const handleDownloadQr = async () => {
    const qrUrl =
      generalData?.qr_code ||
      `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${
        userInfo?.user_id || "unknown"
      }`;

    try {
      const response = await fetch(qrUrl);
      const blob = await response.blob();

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `qr-${userInfo?.user_id || "user"}.png`;
      document.body.appendChild(a);
      a.click();

      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error("Download QR failed", error);
    }
  };

  const displayQrUrl =
    generalData?.qr_code ||
    `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${
      userInfo?.user_id || "unknown"
    }`;

  const items = [
    {
      key: "profile",
      label: (
        <span>
          <User className="inline w-4 h-4 mr-2 mb-1" />
          <span className="hidden sm:inline">{t("info")}</span>
          <span className="sm:hidden">{t("info")}</span>
        </span>
      ),
      children: (
        <Card
          title={t("personal_info")}
          extra={<Text type="secondary">{t("personal_info_desc")}</Text>}>
          <div className="space-y-4">
            <Row gutter={[16, 16]}>
              <Col xs={24} md={12}>
                <div className="space-y-1">
                  <Text strong>{t("full_name")}</Text>
                  <Input
                    defaultValue={userInfo?.fullName}
                    placeholder={t("full_name_placeholder")}
                  />
                </div>
              </Col>
              <Col xs={24} md={12}>
                <div className="space-y-1">
                  <Text strong>{t("email")}</Text>
                  <Input
                    defaultValue={userInfo?.email}
                    placeholder={t("email_placeholder")}
                  />
                </div>
              </Col>
              <Col xs={24} md={12}>
                <div className="space-y-1">
                  <Text strong>{t("phone")}</Text>
                  <Input defaultValue={userInfo?.phone} disabled />
                </div>
              </Col>
              <Col xs={24} md={12}>
                <div className="space-y-1">
                  <Text strong>{t("address")}</Text>
                  <Input placeholder={t("address_placeholder")} />
                </div>
              </Col>
            </Row>

            <div className="flex justify-end pt-4">
              <Button type="primary" icon={<Save className="w-4 h-4" />}>
                {t("save_changes")}
              </Button>
            </div>
          </div>
        </Card>
      ),
    },
    {
      key: "company",
      label: (
        <span>
          <Building2 className="inline w-4 h-4 mr-2 mb-1" />
          <span className="hidden sm:inline">{t("company")}</span>
          <span className="sm:hidden">{t("company")}</span>
        </span>
      ),
      children: (
        <Card
          title={t("company_info")}
          extra={<Text type="secondary">{t("company_info_desc")}</Text>}>
          <div className="space-y-4">
            <div className="space-y-1">
              <Text strong>{t("short_name")}</Text>
              <Input
                prefix={<Building2 className="h-4 w-4 text-slate-500" />}
                value={userInfo?.company_short_name || ""}
                readOnly
                placeholder={t("no_info")}
              />
            </div>
            <div className="space-y-1">
              <Text strong>{t("full_company_name")}</Text>
              <Input
                value={userInfo?.company_name || ""}
                readOnly
                placeholder={t("no_info")}
              />
            </div>
          </div>
        </Card>
      ),
    },
    {
      key: "qr",
      label: (
        <span>
          <QrCode className="inline w-4 h-4 mr-2 mb-1" /> {tc("qr_code")}
        </span>
      ),
      children: (
        <Card
          title={t("qr_code_title")}
          extra={<Text type="secondary">{t("qr_code_desc")}</Text>}>
          <div className="flex flex-col items-center justify-center py-6">
            <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
              <img
                src={displayQrUrl}
                alt="QR Code"
                className="w-56 h-56 object-contain"
              />
            </div>
            <div className="text-center mt-4">
              <Title level={4} style={{ margin: 0 }}>
                {userInfo?.fullName}
              </Title>
            </div>
            <div className="mt-6">
              <Button
                icon={<Download className="w-4 h-4" />}
                onClick={handleDownloadQr}>
                {t("download")}
              </Button>
            </div>
          </div>
        </Card>
      ),
    },
    {
      key: "upgrade",
      label: (
        <span>
          <Briefcase className="inline w-4 h-4 mr-2 mb-1" />
          <span className="hidden sm:inline">{t("upgrade_account")}</span>
          <span className="sm:hidden">{t("upgrade_account")}</span>
        </span>
      ),
      children: (
        <div className="space-y-6">
          <div className="flex justify-end pt-2">
            <ConfirmTooltipButton
              tooltip={t("upgrade_confirm_tooltip")}
              icon={<Save className="w-4 h-4" />}
              onConfirm={handleUpgradeAccount}
              type="primary"
              loading={upgradeMutation.isPending}
              disabled={
                selectedModules.length === upgradedRoles.length ||
                upgradeMutation.isPending
              }>
              {t("save_changes")}
            </ConfirmTooltipButton>
          </div>
          <Row gutter={[24, 24]}>
            {[
              { type: REGISTER_TYPE.FARMER, icon: Tractor },
              { type: REGISTER_TYPE.PURCHASER, icon: ShoppingCart },
              { type: REGISTER_TYPE.TRANSPORTER, icon: Truck },
              { type: REGISTER_TYPE.FACTORY, icon: Factory },
              { type: REGISTER_TYPE.BUSINESS, icon: Briefcase },
            ].map((module) => {
              const isUpgraded = upgradedRoles.includes(module.type);
              const isSelected = selectedModules.includes(module.type);
              return (
                <Col xs={24} md={12} lg={8} key={module.type}>
                  <Card
                    hoverable={!isUpgraded}
                    className={`h-full flex flex-col transition-all ${
                      isSelected
                        ? "border-primary shadow-md ring-1 ring-primary/20"
                        : ""
                    } ${isUpgraded ? "opacity-80" : ""}`}
                    onClick={() => !isUpgraded && toggleModule(module.type)}>
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center gap-4">
                        <div
                          className={`p-3 rounded-lg ${
                            isSelected
                              ? "bg-primary/10 text-primary"
                              : "bg-slate-100"
                          }`}>
                          <module.icon className="w-6 h-6" />
                        </div>
                        <div>
                          <Text strong className="text-lg block">
                            {tr(module.type)}
                          </Text>
                          <Text type="secondary" style={{ fontSize: 12 }}>
                            {t("account_package")}
                          </Text>
                        </div>
                      </div>
                      <Checkbox
                        checked={isSelected}
                        disabled={isUpgraded}
                        onChange={(e) => {
                          e.stopPropagation();
                          toggleModule(module.type);
                        }}
                      />
                    </div>
                    <Divider style={{ margin: "12px 0" }} />
                    <ul className="space-y-2 flex-grow list-none p-0">
                      {tr
                        .raw(`functionalities.${module.type}`)
                        ?.map((func: string, index: number) => (
                          <li
                            key={index}
                            className="flex items-start gap-2 text-sm text-slate-600">
                            <span className="mt-1.5 w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0" />
                            {func}
                          </li>
                        ))}
                    </ul>
                    {isUpgraded && (
                      <div className="mt-4 text-center">
                        <Tag color="success">{t("upgraded")}</Tag>
                      </div>
                    )}
                  </Card>
                </Col>
              );
            })}
          </Row>
        </div>
      ),
    },
  ];

  return (
    <div className="container mx-auto space-y-6 p-4 md:py-10 bg-slate-50/50 min-h-screen">
      <Card className="shadow-md">
        <div className="flex flex-col items-start gap-6 md:flex-row md:items-center p-2">
          <div className="relative group">
            <AntdAvatar
              size={120}
              src={userInfo?.avatar || ""}
              icon={<User />}
              className="border-4 border-white shadow-sm"
              style={{ backgroundColor: "#f1f5f9", color: "#64748b" }}>
              {nameParts.firstName?.[0] || "U"}
            </AntdAvatar>
            <Button
              shape="circle"
              icon={<Camera size={16} />}
              className="absolute -right-2 -bottom-2 bg-white shadow-md border-slate-200"
            />
          </div>

          <div className="flex-1 space-y-2">
            <div className="flex flex-col gap-2 md:flex-row md:items-center">
              <Title level={2} style={{ margin: 0 }}>
                {userInfo?.fullName || t("not_updated_name")}
              </Title>
              <UserRolesBadge roles={userInfo?.user_role || []} />
            </div>

            <div className="text-slate-500 flex flex-wrap gap-4 text-sm font-medium">
              {userInfo?.email && (
                <div className="flex items-center gap-1.5">
                  <Mail className="size-4" />
                  {userInfo.email}
                </div>
              )}
              {userInfo?.phone && (
                <div className="flex items-center gap-1.5">
                  <Phone className="size-4" />
                  {userInfo.phone}
                </div>
              )}
            </div>
          </div>
        </div>
      </Card>

      <div className="bg-white rounded-lg shadow-sm">
        <Tabs
          activeKey={activeTab}
          onChange={setActiveTab}
          items={items}
          className="px-4 py-2"
          tabBarStyle={{ marginBottom: 20 }}
        />
      </div>
    </div>
  );
}
