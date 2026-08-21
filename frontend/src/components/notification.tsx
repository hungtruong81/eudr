"use client";

import React, { useEffect, useState } from "react";
import { useQuery, useQueryClient, useMutation } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import { Badge, Button, Empty, Popover, Typography, Spin } from "antd";

import { cn } from "@/lib/utils";
import { INotification } from "@/types/notifi";
import {
  ListNotification,
  MarkAsReadNotification,
} from "@/lib/apis/notification";

const { Text } = Typography;

const NotificationComponent: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const queryClient = useQueryClient();

  const { data, isLoading, refetch } = useQuery({
    queryKey: ["notifications"],
    queryFn: async () =>
      ListNotification({
        page: 1,
        limit: 10,
        status: "all",
      }),
    refetchInterval: document.visibilityState === "visible" ? 50000 : false,
    refetchOnWindowFocus: false,
  });

  const notifications: INotification[] = Array.isArray(data?.data?.records)
    ? data?.data?.records
    : [];
  const markAsReadMutation = useMutation({
    mutationFn: (ids: string[]) =>
      MarkAsReadNotification({ notification_ids: ids }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
    },
  });

  // Chỉ refetch khi người dùng mở Popover
  useEffect(() => {
    if (isOpen) refetch();
  }, [isOpen, refetch]);

  const handleMarkAsRead = (id: string) => {
    markAsReadMutation.mutate([id]);
  };

  const handleMarkAllAsRead = () => {
    const unreadIds = notifications
      .filter((n) => !n.read_at)
      .map((n) => n.notification_id.toString());
    if (unreadIds.length > 0) {
      markAsReadMutation.mutate(unreadIds);
    }
  };

  const unreadCount = notifications.filter((n) => !n.read_at).length || 0;

  // Giao diện bên trong hộp thoại (Popover)
  const popoverContent = (
    <div className="w-80 sm:w-96 flex flex-col gap-2">
      <div className="flex justify-between items-center border-b pb-2">
        <span className="text-lg font-semibold text-gray-800">Thông báo</span>
        <Button
          type="link"
          size="small"
          onClick={handleMarkAllAsRead}
          loading={markAsReadMutation.isPending}
          disabled={unreadCount === 0}>
          Đọc tất cả
        </Button>
      </div>

      <div className="h-72 overflow-y-auto custom-scrollbar -mx-4 px-4">
        {isLoading ? (
          <div className="flex justify-center items-center h-full text-gray-400">
            <Spin tip="Đang tải..." />
          </div>
        ) : notifications.length === 0 ? (
          <Empty
            image={Empty.PRESENTED_IMAGE_SIMPLE}
            description="Không có thông báo nào"
          />
        ) : (
          <div className="flex flex-col gap-2">
            {notifications.map((notification) => {
              const isRead = !!notification.read_at;

              return (
                <button
                  type="button"
                  key={notification.notification_id}
                  className={cn(
                    "w-full cursor-pointer rounded-lg p-3 text-left transition-all hover:bg-gray-100",
                    isRead ? "bg-transparent" : "bg-blue-50/50",
                  )}
                  onClick={() =>
                    !isRead &&
                    handleMarkAsRead(notification.notification_id.toString())
                  }>
                  <div className="flex flex-col w-full">
                    <Text
                      strong={!isRead}
                      className={isRead ? "text-gray-600" : "text-gray-900"}>
                      {notification.title}
                    </Text>
                    <Text className="text-xs text-gray-500 mt-1 line-clamp-2">
                      {notification.message}
                    </Text>
                    <Text className="text-[11px] text-gray-400 mt-2">
                      {notification.created_at
                        ? new Date(notification.created_at).toLocaleString(
                            "vi-VN",
                          )
                        : ""}
                    </Text>
                  </div>
                </button>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );

  return (
    <Popover
      content={popoverContent}
      trigger="click"
      open={isOpen}
      onOpenChange={setIsOpen}
      placement="bottomRight"
      arrow={false}
      styles={{
        content: {
          padding: "16px 16px 8px 16px",
        },
      }}>
      <Badge count={unreadCount} size="small" offset={[-4, 4]}>
        <Button
          type="text"
          icon={<Bell className="h-5 w-5 text-gray-600" />}
          className="flex items-center justify-center rounded-full hover:bg-gray-100"
          style={{ width: 40, height: 40 }}
        />
      </Badge>
    </Popover>
  );
};

export default NotificationComponent;
