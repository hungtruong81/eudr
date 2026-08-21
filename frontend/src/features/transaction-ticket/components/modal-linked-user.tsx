import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IUserRole, RoleTagList } from "@/components/role-tag";
import {
  getConnections,
  IGetConnectionParams,
} from "@/features/connect/manage-connect/actions";
import NewConnectModal from "@/features/connect/manage-connect/components/new-connect-modal";
import { IConnection } from "@/features/connect/manage-connect/types";
import { useQuery } from "@tanstack/react-query";
import { Button, Modal, Space } from "antd";
import React from "react";

interface IModalLinkedUserProps {
  open: boolean;
  onClose: () => void;
  onSelect: (user: IConnection) => void;
}

import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const ModalLinkedUser = ({
  open,
  onClose,
  onSelect,
}: IModalLinkedUserProps) => {
  const t = useTranslations("TransactionTicket");
  const tCommon = useTranslations("Common");
  const [params, setParams] = React.useState<Partial<IGetConnectionParams>>({
    type: "all",
    status: "accepted",
    page: 1,
    limit: 10,
  });

  const [isModalOpen, setIsModalOpen] = React.useState(false);

  const { data, refetch } = useQuery({
    queryKey: ["connections", params],
    queryFn: () => getConnections(params),
  });

  const columns: CustomColumnTypeTable<IConnection>[] = [
    {
      title: tCommon("full_name"),
      dataIndex: "full_name",
      render: (text) => <b>{text}</b>,
    },
    { title: tCommon("email"), dataIndex: "email" },
    { title: tCommon("phone_number"), dataIndex: "phone" },
    {
      title: t("account_type"),
      dataIndex: "user_roles",
      render: (roles: IUserRole[]) => <RoleTagList roles={roles} />,
      autoFilter: false,
    },
    {
      title: tCommon("action"),
      dataIndex: "actions",
      fixed: "right",
      render: (_, record) => (
        <Button type="primary" onClick={() => onSelect(record)}>
          {t("link_action")}
        </Button>
      ),
    },
  ];
  return (
    <AppModal
      title={t("link_modal_title")}
      open={open}
      onOk={onClose}
      onCancel={onClose}
      width={1000}
      footer={null}>
      <Space orientation="vertical" style={{ width: "100%" }}>
        <Button onClick={() => setIsModalOpen(true)}>
          {t("new_link_button")}
        </Button>
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
      </Space>

      <NewConnectModal
        open={isModalOpen}
        onCancel={() => setIsModalOpen(false)}
        onSuccess={() => refetch()}
      />
    </AppModal>
  );
};

export default ModalLinkedUser;
