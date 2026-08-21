import React from "react";
import { Tag } from "antd";

interface Role {
  role_id: number;
  description: string;
  name: string;
}

interface UserRolesBadgeProps {
  roles?: Role[];
}

const UserRolesBadge: React.FC<UserRolesBadgeProps> = ({ roles }) => {
  if (!roles || roles.length === 0) return null;

  return (
    <div className="flex flex-wrap gap-2">
      {roles.map((role) => (
        <Tag color="blue" key={role.role_id}>
          {role.description}
        </Tag>
      ))}
    </div>
  );
};

export default UserRolesBadge;
