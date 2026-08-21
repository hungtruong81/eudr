import {
  Space,
  Spin,
  Tabs,
  Button,
  List,
  Avatar,
  Typography,
  Divider,
  Row,
  Col,
  Checkbox,
  Input,
  Pagination,
  Card,
  Tooltip,
} from "antd";
import React, { useEffect, useState, useMemo } from "react";
import {
  getMembers,
  getPermissions,
  AssignMemberToGroup,
  setGroupPermission,
} from "../actions";
import {
  ICompanyGroupByCode,
  IPermission,
  IUserCompany,
  IGroupMember,
} from "../types";
import BaseSheet from "@/components/shared/base-sheet";
import {
  UserAddOutlined,
  UserOutlined,
  DeleteOutlined,
  SearchOutlined,
  InfoCircleOutlined,
} from "@ant-design/icons";
import { handleApiError } from "@/lib/api-error";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import UserSelectionModal from "./user-selection-modal";
import { REGISTER_TYPE, REGISTER_TYPE_LABEL } from "@/constants/register-types";
import { useDebounce } from "@/hooks/use-debounce";
import { useTranslations } from "next-intl";

const { Text } = Typography;

export type IGroupedPermissionItem = {
  id: number;
  code: string;
  label: string;
  action: string;
  scope: string;
  description: string;
};

export type IGroupedModule = {
  moduleKey: string;
  moduleLabel: string;
  allPermissions: IGroupedPermissionItem[];
  scopes: Record<string, IGroupedPermissionItem[]>;
};

interface GroupPermissionModalProps {
  open: boolean;
  onClose: () => void;
  record: ICompanyGroupByCode | null;
  onSuccess?: () => void;
  onFinish: (perms: string[]) => Promise<any>;
  loading: boolean;
}

const GroupPermissionModal = ({
  open,
  onClose,
  record,
  onSuccess,
  onFinish,
  loading,
}: GroupPermissionModalProps) => {
  const queryClient = useQueryClient();
  const t = useTranslations("Manage.GroupPermission");
  const tc = useTranslations("Common");
  const [activeTab, setActiveTab] = useState("members");

  // Member State
  const [assignedUsers, setAssignedUsers] = useState<IUserCompany[]>([]);
  const [initialUsers, setInitialUsers] = useState<IUserCompany[]>([]);
  const [userSelectorOpen, setUserSelectorOpen] = useState(false);
  const [memberSearch, setMemberSearch] = useState("");
  const [memberPage, setMemberPage] = useState(1);
  const debounceMemberSearch = useDebounce(memberSearch, 300);

  // Permission State
  const [permissions, setPermissions] = useState<IPermission[]>([]);
  const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
  const [fetchingPermissions, setFetchingPermissions] = useState(false);

  // Mutations
  const assignMemberMutation = useMutation({
    mutationFn: (data: { assign: number[]; remove: number[] }) =>
      AssignMemberToGroup(record!.company_group_code, {
        assign_user_ids: data.assign,
        remove_user_ids: data.remove,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["group-members"] });
      onSuccess?.();
      onClose();
    },
    onError: (error) => handleApiError(error),
  });

  const permissionMutation = useMutation({
    mutationFn: (perms: string[]) =>
      setGroupPermission(record!.company_group_code, { permissions: perms }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["company-groups"] });
      onSuccess?.();
      onClose();
    },
    onError: (error) => handleApiError(error),
  });

  // Fetch Members
  const { data: memberData, isLoading: isLoadingMembers } = useQuery({
    queryKey: [
      "group-members",
      record?.company_group_code,
      record?.company_id,
      memberPage,
      debounceMemberSearch,
    ],
    queryFn: () =>
      getMembers({
        company_group_code: record!.company_group_code,
        company_id: record!.company_id,
        page: memberPage,
        limit: 20,
        search: debounceMemberSearch,
      }),
    enabled: !!record && open && activeTab === "members",
  });

  useEffect(() => {
    if (memberData?.data?.records) {
      setAssignedUsers(memberData.data.records);
      if (memberPage === 1 && !debounceMemberSearch) {
        setInitialUsers(memberData.data.records);
      }
    }
  }, [memberData, debounceMemberSearch, memberPage]);

  // Fetch Permissions
  useEffect(() => {
    if (open && activeTab === "permissions") {
      if (permissions.length === 0) {
        fetchAllPermissions();
      }
      if (record?.permissions) {
        setSelectedPermissions(record.permissions);
      }
    }
  }, [open, record, activeTab, permissions.length]);

  const fetchAllPermissions = async () => {
    setFetchingPermissions(true);
    try {
      const res = await getPermissions();
      if (res?.data) {
        setPermissions(res.data);
      }
    } catch (error) {
      handleApiError(error);
    } finally {
      setFetchingPermissions(false);
    }
  };

  // Logic Helpers
  const groupedModules = useMemo<IGroupedModule[]>(() => {
    const modulesMap: Record<string, IGroupedModule> = {};
    permissions.forEach((perm) => {
      const moduleKey = perm.module;
      const scopeKey = perm.scope || "action";
      if (!modulesMap[moduleKey]) {
        modulesMap[moduleKey] = {
          moduleKey: moduleKey,
          moduleLabel: t(`module_labels.${moduleKey as any}`),
          allPermissions: [],
          scopes: {},
        };
      }
      if (!modulesMap[moduleKey].scopes[scopeKey]) {
        modulesMap[moduleKey].scopes[scopeKey] = [];
      }
      const item: IGroupedPermissionItem = {
        id: perm.permission_id,
        code: perm.name,
        label: perm.display_name,
        action: perm.action,
        scope: scopeKey,
        description: perm.description,
      };
      modulesMap[moduleKey].allPermissions.push(item);
      modulesMap[moduleKey].scopes[scopeKey].push(item);
    });
    return Object.values(modulesMap).sort((a, b) =>
      a.moduleLabel.localeCompare(b.moduleLabel),
    );
  }, [permissions, t]);

  const handleTogglePermission = (code: string) => {
    setSelectedPermissions((prev) =>
      prev.includes(code) ? prev.filter((p) => p !== code) : [...prev, code],
    );
  };

  const handleToggleModule = (modulePermissions: IGroupedPermissionItem[]) => {
    const codes = modulePermissions.map((p) => p.code);
    const isAllChecked = modulePermissions.every((p) =>
      selectedPermissions.includes(p.code),
    );
    setSelectedPermissions((prev) =>
      isAllChecked
        ? prev.filter((p) => !codes.includes(p))
        : Array.from(new Set([...prev, ...codes])),
    );
  };

  const handleRemoveUser = (userId: number) => {
    setAssignedUsers((prev) => prev.filter((u) => u.user_id !== userId));
  };

  const handleAddUsers = (newUsers: IUserCompany[]) => {
    setAssignedUsers((prev) => {
      const unique = newUsers.filter(
        (nu) => !prev.some((ex) => ex.user_id === nu.user_id),
      );
      return [...prev, ...unique];
    });
  };

  const handleSave = () => {
    if (activeTab === "members") {
      const currentIds = assignedUsers.map((u) => u.user_id);
      const initialIds = initialUsers.map((u) => u.user_id);
      const assign = currentIds.filter((id) => !initialIds.includes(id));
      const remove = initialIds.filter((id) => !currentIds.includes(id));
      assignMemberMutation.mutate({ assign, remove });
    } else {
      permissionMutation.mutate(selectedPermissions);
    }
  };

  const isModuleChecked = (perms: IGroupedPermissionItem[]) =>
    perms.length > 0 &&
    perms.every((p) => selectedPermissions.includes(p.code));
  const isModuleIndeterminate = (perms: IGroupedPermissionItem[]) => {
    const selectedCount = perms.filter((p) =>
      selectedPermissions.includes(p.code),
    ).length;
    return selectedCount > 0 && selectedCount < perms.length;
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={handleSave}
      width={"100%"}
      title={
        <Space orientation="vertical" size={0}>
          <Text strong style={{ fontSize: 18, color: "#1890ff" }}>
            {t("manage_group_title", { name: record?.name! })}
          </Text>
          <Text type="secondary" style={{ fontSize: 12 }}>
            {record?.company_group_code} | {record?.company_name}
          </Text>
        </Space>
      }
      loading={
        assignMemberMutation.status === "pending" ||
        permissionMutation.status === "pending"
      }
      okText={tc("save_changes")}>
      <Tabs
        activeKey={activeTab}
        onChange={setActiveTab}
        items={[
          {
            key: "members",
            label: t("tab_members"),
            children: (
              <div
                style={{ display: "flex", flexDirection: "column", gap: 16 }}>
                <div
                  style={{
                    display: "flex",
                    justifyContent: "space-between",
                    alignItems: "center",
                  }}>
                  <Button
                    type="primary"
                    icon={<UserAddOutlined />}
                    onClick={() => setUserSelectorOpen(true)}>
                    {t("add_users")}
                  </Button>
                  <Input
                    placeholder={t("search_members")}
                    prefix={<SearchOutlined />}
                    style={{ width: 300 }}
                    value={memberSearch}
                    onChange={(e) => {
                      setMemberSearch(e.target.value);
                      setMemberPage(1);
                    }}
                    allowClear
                  />
                </div>

                {isLoadingMembers ? (
                  <div style={{ textAlign: "center", padding: 50 }}>
                    <Spin size="large" />
                  </div>
                ) : assignedUsers.length === 0 ? (
                  <Card
                    style={{
                      textAlign: "center",
                      padding: 40,
                      borderStyle: "dashed",
                      borderColor: "#d9d9d9",
                    }}>
                    <Text type="secondary">{t("empty_group")}</Text>
                  </Card>
                ) : (
                  <>
                    <Row gutter={[16, 16]}>
                      {assignedUsers.map((user) => (
                        <Col xs={24} sm={12} lg={8} xl={6} key={user.user_id}>
                          <div
                            style={{
                              padding: 12,
                              border: "1px solid #f0f0f0",
                              borderRadius: 8,
                              backgroundColor: "#fff",
                              display: "flex",
                              justifyContent: "space-between",
                              alignItems: "center",
                              transition: "all 0.3s",
                            }}>
                            <Space align="start">
                              <Avatar
                                icon={<UserOutlined />}
                                src={user.avatar}
                                size={40}
                              />
                              <div
                                style={{
                                  display: "flex",
                                  flexDirection: "column",
                                  maxWidth: 160,
                                }}>
                                <Text strong ellipsis title={user.full_name}>
                                  {user.full_name}
                                </Text>
                                <Text type="secondary" style={{ fontSize: 11 }}>
                                  {user.phone}
                                </Text>
                                <Text
                                  type="secondary"
                                  style={{ fontSize: 11 }}
                                  ellipsis
                                  title={user.email}>
                                  {user.email}
                                </Text>
                              </div>
                            </Space>
                            <Tooltip title={t("remove_tooltip")}>
                              <Button
                                type="text"
                                danger
                                icon={<DeleteOutlined />}
                                onClick={() => handleRemoveUser(user.user_id)}
                              />
                            </Tooltip>
                          </div>
                        </Col>
                      ))}
                    </Row>
                    <div
                      style={{ display: "flex", justifyContent: "flex-end" }}>
                      <Pagination
                        current={memberPage}
                        total={memberData?.data?.total_records || 0}
                        pageSize={20}
                        onChange={setMemberPage}
                        showSizeChanger={false}
                      />
                    </div>
                  </>
                )}
              </div>
            ),
          },
          {
            key: "permissions",
            label: t("tab_permissions"),
            children: (
              <div
                style={{
                  maxHeight: "calc(100vh - 200px)",
                  overflowY: "auto",
                  paddingRight: 8,
                }}>
                {fetchingPermissions ? (
                  <div style={{ textAlign: "center", padding: 50 }}>
                    <Spin size="large" />
                  </div>
                ) : (
                  <Space orientation="vertical" style={{ width: "100%" }}>
                    {groupedModules.map((module) => (
                      <div
                        key={module.moduleKey}
                        style={{
                          borderRadius: 8,
                          border: "1px solid #f0f0f0",
                          backgroundColor: "#fff",
                          overflow: "hidden",
                        }}>
                        <div
                          style={{
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "space-between",
                            padding: "12px 16px",
                            backgroundColor: "#fafafa",
                            borderBottom: "1px solid #f0f0f0",
                          }}>
                          <div>
                            <Text
                              strong
                              style={{
                                fontSize: 15,
                                color: "#1890ff",
                                textTransform: "uppercase",
                              }}>
                              {module.moduleLabel}
                            </Text>
                            <div style={{ fontSize: 11, color: "#8c8c8c" }}>
                              (Module: {module.moduleKey})
                            </div>
                          </div>
                          <Checkbox
                            checked={isModuleChecked(module.allPermissions)}
                            indeterminate={isModuleIndeterminate(
                              module.allPermissions,
                            )}
                            onChange={() =>
                              handleToggleModule(module.allPermissions)
                            }>
                            {t("select_all")}
                          </Checkbox>
                        </div>
                        <div style={{ padding: "16px 24px" }}>
                          <Space
                            orientation="vertical"
                            style={{ width: "100%" }}
                            size="middle">
                            {Object.entries(module.scopes).map(
                              ([scopeKey, scopePermissions]) => (
                                <Row key={scopeKey} gutter={[16, 16]}>
                                  <Col xs={24} md={4}>
                                    <Text
                                      strong
                                      style={{
                                        color: "#595959",
                                        textTransform: "capitalize",
                                      }}>
                                      {t.has(`scope_labels.${scopeKey}`) ? t(`scope_labels.${scopeKey}`) : scopeKey}
                                    </Text>
                                  </Col>
                                  <Col xs={24} md={20}>
                                    <Row gutter={[16, 16]}>
                                      {scopePermissions.map((perm) => (
                                        <Col
                                          xs={24}
                                          sm={12}
                                          lg={8}
                                          xl={6}
                                          key={perm.id}>
                                          <Checkbox
                                            checked={selectedPermissions.includes(
                                              perm.code,
                                            )}
                                            onChange={() =>
                                              handleTogglePermission(perm.code)
                                            }>
                                            <div
                                              style={{
                                                display: "flex",
                                                flexDirection: "column",
                                              }}>
                                              <Text style={{ fontSize: 13 }}>
                                                {perm.label}
                                              </Text>
                                              {perm.description && (
                                                <Tooltip
                                                  title={perm.description}>
                                                  <Text
                                                    type="secondary"
                                                    style={{ fontSize: 11 }}
                                                    ellipsis>
                                                    {perm.description}
                                                  </Text>
                                                </Tooltip>
                                              )}
                                            </div>
                                          </Checkbox>
                                        </Col>
                                      ))}
                                    </Row>
                                  </Col>
                                  <Divider style={{ margin: "4px 0" }} />
                                </Row>
                              ),
                            )}
                          </Space>
                        </div>
                      </div>
                    ))}
                  </Space>
                )}
              </div>
            ),
          },
        ]}
      />
      <UserSelectionModal
        open={userSelectorOpen}
        onClose={() => setUserSelectorOpen(false)}
        onAddUsers={handleAddUsers}
        existingUserIds={assignedUsers.map((u) => u.user_id)}
      />
    </BaseSheet>
  );
};

export default GroupPermissionModal;
