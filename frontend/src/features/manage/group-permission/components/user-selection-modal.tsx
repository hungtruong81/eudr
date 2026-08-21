"use client";

import { useDebounce } from "@/hooks/use-debounce";
import { useUser } from "@/providers/user-context";
import { useQuery } from "@tanstack/react-query";
import {
  Avatar,
  Button,
  Checkbox,
  Col,
  Input,
  List,
  Modal,
  Pagination,
  Row,
  Select,
  Space,
  Spin,
  Typography,
} from "antd";
import React, { useState } from "react";
import { getListUserCompany } from "../actions";
import { IUserCompany } from "../types";
import { useTranslations } from "next-intl";
import { REGISTER_TYPE, REGISTER_TYPE_LABEL } from "@/constants/register-types";
import {
  SearchOutlined,
  ReloadOutlined,
  UserOutlined,
} from "@ant-design/icons";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getCompanys } from "../../company/actions";
import { ICompany } from "../../company/types";
import AppModal from "@/components/modal";
import { RoleTagList } from "@/components/role-tag";

const { Text } = Typography;

interface GroupPermissionUsersProps {
  open: boolean;
  onClose: () => void;
  onAddUsers: (users: IUserCompany[]) => void;
  existingUserIds: number[];
}

const GroupPermissionUsers = ({
  open,
  onClose,
  onAddUsers,
  existingUserIds,
}: GroupPermissionUsersProps) => {
  const { isAdmin } = useUser();
  const t = useTranslations("Manage.GroupPermission");
  const tc = useTranslations("Common");
  const tr = useTranslations("RegisterType");

  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [accountType, setAccountType] = useState<string | undefined>(undefined);
  const [companyId, setCompanyId] = useState<string | undefined>(undefined);
  const limit = 5;

  const [selectedUsers, setSelectedUsers] = useState<IUserCompany[]>([]);

  const debounceSearch = useDebounce(search, 300);

  const { data: userListResponse, isLoading } = useQuery({
    queryKey: [
      "all-users",
      page,
      limit,
      debounceSearch,
      accountType,
      companyId,
    ],
    queryFn: () =>
      getListUserCompany({
        page,
        limit,
        search: debounceSearch,
        register_type: accountType,
        company_id: isAdmin ? companyId : undefined,
      }),
    enabled: open,
  });

  const users = userListResponse?.data?.records || [];
  const total = userListResponse?.data?.total_records || 0;

  const handleToggleUser = (user: IUserCompany) => {
    const isAlreadyAdded = existingUserIds.includes(user.user_id);
    if (isAlreadyAdded) return;

    setSelectedUsers((prev) => {
      const exists = prev.find((u) => u.user_id === user.user_id);
      if (exists) {
        return prev.filter((u) => u.user_id !== user.user_id);
      } else {
        return [...prev, user];
      }
    });
  };

  const handleSubmit = () => {
    onAddUsers(selectedUsers);
    handleClose();
  };

  const handleClearFilter = () => {
    setSearch("");
    setAccountType(undefined);
    setCompanyId(undefined);
    setPage(1);
  };

  const handleClose = () => {
    setSelectedUsers([]);
    onClose();
  };

  return (
    <AppModal
      title={t("add_members_title")}
      open={open}
      onCancel={handleClose}
      onOk={handleSubmit}
      okButtonProps={{ disabled: selectedUsers.length === 0 }}
      okText={t("add_btn", {
        count: selectedUsers.length > 0 ? `(${selectedUsers.length})` : "",
      })}
      cancelText={tc("cancel")}
      width={1024}
      styles={{
        body: { height: "70vh", overflowY: "auto" },
      }}>
      <Space orientation="vertical" style={{ width: "100%" }}>
        <Row gutter={16}>
          <Col span={8}>
            <Input
              placeholder={t("search_users_placeholder")}
              prefix={<SearchOutlined />}
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
              allowClear
            />
          </Col>
          <Col span={6}>
            <Select
              style={{ width: "100%" }}
              placeholder={tc("status_all")}
              value={accountType}
              onChange={(val) => {
                setAccountType(val);
                setPage(1);
              }}
              allowClear
              options={Object.values(REGISTER_TYPE).map((type) => ({
                label: tr(type),
                value: type,
              }))}
            />
          </Col>
          {isAdmin && (
            <Col span={6}>
              <InfiniteScrollSelect<ICompany>
                queryKey={["factories-filter"]}
                fetchFn={getCompanys}
                mapOption={(item) => ({
                  label: item.company_name,
                  value: String(item.company_id),
                })}
                placeholder={t("filter_company")}
                allowClear
              />
            </Col>
          )}
          <Col span={4} style={{ display: "flex", alignItems: "flex-end" }}>
            <Button icon={<ReloadOutlined />} onClick={handleClearFilter}>
              {tc("clear_filter")}
            </Button>
          </Col>
        </Row>

        <div
          style={{
            border: "1px solid #f0f0f0",
            borderRadius: 8,
            padding: 8,
            minHeight: 300,
          }}>
          {isLoading ? (
            <div style={{ textAlign: "center", padding: 50 }}>
              <Spin description={t("loading_users")} />
            </div>
          ) : (
            <List
              dataSource={users}
              renderItem={(user) => {
                const isSelected = selectedUsers.some(
                  (u) => u.user_id === user.user_id,
                );
                const isAlreadyAdded = existingUserIds.includes(user.user_id);

                return (
                  <List.Item
                    onClick={() => handleToggleUser(user)}
                    style={{
                      cursor: isAlreadyAdded ? "not-allowed" : "pointer",
                      backgroundColor: isSelected ? "#e6f7ff" : "transparent",
                      padding: "8px 16px",
                      borderRadius: 4,
                      opacity: isAlreadyAdded ? 0.5 : 1,
                    }}>
                    <Space size="middle" style={{ width: "100%" }}>
                      <Checkbox
                        checked={isSelected || isAlreadyAdded}
                        disabled={isAlreadyAdded}
                      />
                      <Avatar icon={<UserOutlined />} src={user.avatar} />
                      <div style={{ display: "flex", flexDirection: "column" }}>
                        <Text strong>
                          {user.full_name}{" "}
                          <Text type="secondary">
                            ({user.phone} - {user.email})
                          </Text>
                        </Text>
                        <Text type="secondary" style={{ fontSize: 12 }}>
                          <RoleTagList roles={user.user_roles} />
                        </Text>
                      </div>
                    </Space>
                  </List.Item>
                );
              }}
              locale={{ emptyText: t("no_users_found") }}
            />
          )}
        </div>

        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
          }}>
          <Text type="secondary">
            {t("selected_count", { count: selectedUsers.length })}
          </Text>
          <Pagination
            current={page}
            total={total}
            pageSize={limit}
            onChange={(p) => setPage(p)}
            showSizeChanger={false}
            simple
          />
        </div>
      </Space>
    </AppModal>
  );
};

export default GroupPermissionUsers;
