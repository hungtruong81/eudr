"use client";
import { RoleTagList } from "@/components/role-tag";
import {
  Button,
  Card,
  Checkbox,
  Empty,
  Form,
  Input,
  message,
  Modal,
  Space,
  Table,
  Typography,
} from "antd";
import React, { useState } from "react";
import { connectionRequest, searchUser } from "../actions";
import { ISearchUser } from "../types";
import { handleApiError } from "@/lib/api-error";

import { useTranslations } from "next-intl";
import Text from "antd/es/typography/Text";
import AppModal from "@/components/modal";

interface NewConnectModalProps {
  open: boolean;
  onCancel: () => void;
  onSuccess: () => void;
}

const NewConnectModal: React.FC<NewConnectModalProps> = ({
  open,
  onCancel,
  onSuccess,
}) => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");

  const detailColumns = [
    {
      title: tCommon("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text>{text}</Text>,
    },
    {
      title: tCommon("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const [form] = Form.useForm();
  const [isSearching, setIsSearching] = useState(false);
  const [isConnecting, setIsConnecting] = useState(false);
  const [foundUser, setFoundUser] = useState<ISearchUser | null>(null);
  const [hasSearched, setHasSearched] = useState(false);

  const [isAgreed, setIsAgreed] = useState(false);

  const onFinish = async (values: { phone: string }) => {
    const { phone } = values;
    const trimmedPhone = phone.trim();

    setIsSearching(true);
    setHasSearched(true);
    setIsAgreed(false);

    try {
      const response = await searchUser(trimmedPhone);
      const user = response?.data;

      if (user) {
        setFoundUser(user as ISearchUser);
      } else {
        setFoundUser(null);
      }
    } catch (error) {
      handleApiError(error);
      setFoundUser(null);
    } finally {
      setIsSearching(false);
    }
  };

  const handleConnect = async () => {
    if (!foundUser) return;

    setIsConnecting(true);
    try {
      await connectionRequest({
        target_user_code: foundUser.user_code,
        connection_method: "phone",
      });
      message.success(tConnection("connection_request_success"));
      onSuccess();
      handleClose();
    } catch (error) {
      handleApiError(error);
    } finally {
      setIsConnecting(false);
    }
  };

  const handleClose = () => {
    form.resetFields();
    setFoundUser(null);
    setHasSearched(false);
    setIsAgreed(false);
    onCancel();
  };

  return (
    <AppModal
      title={tConnection("add_new_connection")}
      open={open}
      onCancel={handleClose}
      footer={null}>
      <Space orientation="vertical" style={{ width: "100%" }}>
        <Form form={form} onFinish={onFinish} layout="vertical">
          <Form.Item
            name="phone"
            rules={[
              { required: true, message: tCommon("phone_required") },
              {
                pattern: /^[0-9]+$/,
                message: tConnection("phone_digits_only"),
              },
            ]}>
            <Input.Search
              placeholder={tConnection("search_phone_placeholder")}
              enterButton={tCommon("search")}
              size="large"
              loading={isSearching}
              onSearch={() => form.submit()}
            />
          </Form.Item>
        </Form>

        {isSearching ? null : foundUser ? (
          <Card
            title={tConnection("user_info")}
            size="small"
            actions={[
              <Button
                key="connect"
                type="primary"
                loading={isConnecting}
                onClick={handleConnect}
                disabled={!isAgreed}>
                {tConnection("send_connection_request")}
              </Button>,
            ]}>
            <Space
              orientation="vertical"
              size="middle"
              style={{ width: "100%" }}>
              <Table
                dataSource={[
                  { label: tCommon("full_name"), value: foundUser.full_name },
                  { label: tCommon("phone_number"), value: foundUser.phone },
                  { label: tCommon("email"), value: foundUser.email },
                  {
                    label: tConnection("role"),
                    value: <RoleTagList roles={foundUser.user_roles} />,
                  },
                ]}
                columns={detailColumns}
                pagination={false}
                size="small"
                bordered
                rowKey="label"
                showHeader={false}
              />

              <Checkbox
                checked={isAgreed}
                onChange={(e) => setIsAgreed(e.target.checked)}>
                {tConnection("agreement_checkbox")}
              </Checkbox>
            </Space>
          </Card>
        ) : hasSearched ? (
          <Empty description={tConnection("user_not_found")} />
        ) : null}
      </Space>
    </AppModal>
  );
};

export default NewConnectModal;
