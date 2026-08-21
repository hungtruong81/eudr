"use client";

import { Modal, Checkbox, Space, Typography, Button, Spin, Empty } from "antd";
import React, { useEffect, useState } from "react";
import { REGISTER_TYPE, REGISTER_TYPE_LABEL } from "@/constants/register-types";
import { IUserCompany } from "../types";
import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const { Text } = Typography;

interface RoleModalProps {
  open: boolean;
  onClose: () => void;
  onFinish: (addRoles: string[], removeRoles: string[]) => Promise<void>;
  loading: boolean;
  record: IUserCompany | null;
}

const RoleModal = ({
  open,
  onClose,
  onFinish,
  loading,
  record,
}: RoleModalProps) => {
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
  const t = useTranslations("Manage.User");
  const tc = useTranslations("Common");
  const tr = useTranslations("RegisterType");

  // Filtered Register Types as per request
  const allowedRoles = [
    {
      label: tr(REGISTER_TYPE.FARMER),
      value: REGISTER_TYPE.FARMER,
    },
    {
      label: tr(REGISTER_TYPE.PURCHASER),
      value: REGISTER_TYPE.PURCHASER,
    },
    {
      label: tr(REGISTER_TYPE.TRANSPORTER),
      value: REGISTER_TYPE.TRANSPORTER,
    },
    {
      label: tr(REGISTER_TYPE.FACTORY),
      value: REGISTER_TYPE.FACTORY,
    },
    {
      label: tr(REGISTER_TYPE.BUSINESS),
      value: REGISTER_TYPE.BUSINESS,
    },
  ];

  useEffect(() => {
    if (open && record) {
      const currentRoles = record.user_roles?.map((r) => r.name) || [];
      setSelectedRoles(currentRoles);
    }
  }, [open, record]);

  const handleSave = async () => {
    if (!record) return;

    const initialRoles = record.user_roles?.map((r) => r.name) || [];
    const add_roles = selectedRoles.filter(
      (role) => !initialRoles.includes(role),
    );
    const remove_roles = initialRoles.filter(
      (role) => !selectedRoles.includes(role),
    );

    await onFinish(add_roles, remove_roles);
  };

  return (
    <AppModal
      title={`${t("assign_roles")}: ${record?.full_name}`}
      open={open}
      onCancel={onClose}
      onOk={handleSave}
      confirmLoading={loading}
      okText={tc("save_changes")}
      cancelText={tc("cancel")}
      width={500}>
      <div style={{ padding: "10px 0" }}>
        <Text type="secondary" style={{ display: "block", marginBottom: 15 }}>
          {t("assign_roles_hint")}
        </Text>
        <Checkbox.Group
          style={{ width: "100%" }}
          value={selectedRoles}
          onChange={(values) => setSelectedRoles(values as string[])}>
          <Space orientation="vertical" style={{ width: "100%" }}>
            {allowedRoles.map((role) => (
              <Checkbox key={role.value} value={role.value}>
                {role.label}{" "}
                <Text type="secondary" style={{ fontSize: 12 }}>
                  ({role.value})
                </Text>
              </Checkbox>
            ))}
          </Space>
        </Checkbox.Group>
      </div>
    </AppModal>
  );
};

export default RoleModal;
