"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import {
  blockRequest,
  cancelRequest,
  getConnections,
  IGetConnectionParams,
  respondRequest,
} from "../actions";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IConnection } from "../types";
import { Button, Dropdown, Flex, MenuProps, Space, Tag } from "antd";
import {
  CheckOutlined,
  CloseOutlined,
  EyeOutlined,
  MoreOutlined,
  PlusOutlined,
  StopOutlined,
  UndoOutlined,
} from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import ConnectionFilter from "./connection-filter";
import { useRouter } from "nextjs-toploader/app";
import { IUserRole, RoleTagList } from "@/components/role-tag";
import { handleApiError } from "@/lib/api-error";
import NewConnectModal from "./new-connect-modal";
import { useUser } from "@/providers/user-context";
import LandShareModal from "./land-share-modal";
import MyLandShareModal from "./my-land-share-modal";

import { useTranslations } from "next-intl";

const Connection = () => {
  const { isFarmer } = useUser();
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");

  const [params, setParams] = React.useState<Partial<IGetConnectionParams>>({
    page: 1,
    limit: 10,
    search: "",
    type: "all",
    status: "all",
  });

  const [isModalOpen, setIsModalOpen] = React.useState(false);
  const [isLandShareModalOpen, setIsLandShareModalOpen] = React.useState(false);
  const [targetUserConnect, setTargetUserConnect] =
    React.useState<IConnection | null>(null);

  const [isMyLandShareModalOpen, setIsMyLandShareModalOpen] =
    React.useState(false);
  const { data, refetch } = useQuery({
    queryKey: ["connections", params],
    queryFn: () => getConnections(params),
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case "accepted":
        return "success";
      case "pending":
        return "processing";
      case "rejected":
        return "error";
      case "cancelled":
        return "default";
      case "blocked":
        return "warning";
      default:
        return "default";
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case "accepted":
        return tConnection("accepted");
      case "pending":
        return tConnection("pending");
      case "rejected":
        return tConnection("rejected");
      case "cancelled":
        return tConnection("cancelled");
      case "blocked":
        return tConnection("blocked");
      default:
        return status;
    }
  };

  const handleRespond = async (
    connection_id: string,
    action: "accept" | "reject",
  ) => {
    try {
      await respondRequest({ connection_id, action });
      refetch();
    } catch (error) {
      handleApiError(error);
    }
  };

  const handleCancel = async (connection_id: string) => {
    try {
      await cancelRequest(connection_id);
      refetch();
    } catch (error) {
      handleApiError(error);
    }
  };

  const handleBlock = async (connection_id: string) => {
    try {
      await blockRequest({ connection_id, action: "block" });
      refetch();
    } catch (error) {
      handleApiError(error);
    }
  };

  const columns: CustomColumnTypeTable<IConnection>[] = [
    {
      title: tCommon("full_name"),
      dataIndex: "full_name",
      render: (text) => <b>{text}</b>,
    },
    { title: tCommon("email"), dataIndex: "email" },
    { title: tCommon("phone_number"), dataIndex: "phone" },
    {
      title: tConnection("account_type"),
      dataIndex: "user_roles",
      render: (roles: IUserRole[]) => <RoleTagList roles={roles} />,
      autoFilter: false,
    },
    {
      title: tConnection("direction"),
      dataIndex: "connection_direction",
      render: (text) =>
        text === "sent" ? tConnection("sent") : tConnection("received"),
    },
    {
      title: tCommon("status"),
      dataIndex: "status",
      render: (text) => (
        <Tag color={getStatusColor(text)}>{getStatusLabel(text)}</Tag>
      ),
    },
    { title: tConnection("requested_at"), dataIndex: "requested_at", type: "date" },
    {
      title: tCommon("actions"),
      dataIndex: "actions",
      fixed: "right",
      render: (_, record) => {
        const isReceived = record.connection_direction === "received";
        const isSent = record.connection_direction === "sent";
        const isPending = record.status === "pending";
        const isAccepted = record.status === "accepted";

        const items: MenuProps["items"] = [];

        if (isReceived && isPending) {
          items.push(
            {
              key: "accept",
              label: tConnection("accept"),
              icon: <CheckOutlined />,
              onClick: () =>
                handleRespond(String(record.connection_id), "accept"),
            },
            {
              key: "reject",
              label: tConnection("reject"),
              icon: <CloseOutlined />,
              danger: true,
              onClick: () =>
                handleRespond(String(record.connection_id), "reject"),
            },
          );
        }

        if (isSent && isPending) {
          items.push({
            key: "cancel",
            label: tConnection("cancel_request"),
            icon: <UndoOutlined />,
            danger: true,
            onClick: () => handleCancel(String(record.connection_id)),
          });
        }

        if (isAccepted || (isReceived && isPending)) {
          items.push({
            key: "block",
            label: tConnection("block"),
            icon: <StopOutlined />,
            danger: true,
            onClick: () => handleBlock(String(record.connection_id)),
          });
        }

        return (
          <Space>
            <TooltipButton
              tooltip={tConnection("view_shared_lands")}
              icon={<EyeOutlined />}
              type="dashed"
              onClick={() => {
                setIsLandShareModalOpen(true);
                setTargetUserConnect(record);
              }}
            />

            <TooltipButton
              tooltip={tConnection("view_lands_shared_to_me")}
              icon={<EyeOutlined />}
              type="dashed"
              onClick={() => {
                setIsMyLandShareModalOpen(true);
                setTargetUserConnect(record);
              }}
            />
            {items.length > 0 && (
              <Dropdown menu={{ items }} trigger={["click"]}>
                <Button icon={<MoreOutlined />} type="text" />
              </Dropdown>
            )}
          </Space>
        );
      },
    },
  ];

  const handleSearch = (newParams: Partial<IGetConnectionParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="center" justify="space-between">
        <ConnectionFilter onSearch={handleSearch} />

        <TooltipButton
          tooltip={tConnection("new_connection")}
          icon={<PlusOutlined />}
          type="primary"
          onClick={() => setIsModalOpen(true)}>
          {tCommon("add")}
        </TooltipButton>
      </Flex>
      <CustomTable
        columns={columns}
        dataSource={data?.data?.records || []}
        rowKey="connection_id"
        tableId="connection-list-table"
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onChange={(pagination) => {
          setParams({
            ...params,
            page: pagination.current || 1,
            limit: pagination.pageSize || 10,
          });
        }}
        scroll={{
          x: "max-content",
        }}
      />

      <NewConnectModal
        open={isModalOpen}
        onCancel={() => setIsModalOpen(false)}
        onSuccess={() => refetch()}
      />

      <LandShareModal
        open={isLandShareModalOpen}
        onClose={() => setIsLandShareModalOpen(false)}
        onSuccess={() => refetch()}
        isFarmer={isFarmer}
        target_user_connect={targetUserConnect}
      />

      <MyLandShareModal
        open={isMyLandShareModalOpen}
        onClose={() => setIsMyLandShareModalOpen(false)}
        target_user_connect={targetUserConnect}
        owner_user_id={
          params.type === "received"
            ? targetUserConnect?.requester_user_id!
            : targetUserConnect?.target_user_id!
        }
      />
    </Space>
  );
};

export default Connection;
