import React, { useState } from 'react';
import {
    BarChart3,
    GraduationCap,
    LogOut,
    Moon,
    PanelLeftClose,
    PanelLeftOpen,
    Sun,
    Upload,
    Users,
    X,
} from 'lucide-react';
import SimdexLogo from './SimdexLogo';

function SidebarLink({ icon: Icon, label, count, target, active, onNavigate }) {
    return (
        <a
            className={`dashboard-sidebar-link${active ? ' active' : ''}`}
            href={`#${target}`}
            title={label}
            onClick={(event) => {
                event.preventDefault();
                onNavigate(target);
            }}
        >
            <Icon aria-hidden="true" size={17} strokeWidth={1.9} />
            <span>{label}</span>
            {count !== undefined ? <b>{count}</b> : null}
        </a>
    );
}

export default function DashboardSidebar({
    analysis,
    collapsed,
    csrf,
    isOpen,
    onClose,
    onNavigate,
    onToggleCollapsed,
    onThemeChange,
    routes,
    stats,
    theme,
    user,
}) {
    const [activeItem, setActiveItem] = useState('students-table');
    const initials = user.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');

    const navigate = (target) => {
        setActiveItem(target);
        onNavigate(target);
    };

    return (
        <aside className={`dashboard-sidebar${isOpen ? ' open' : ''}`} aria-label="Dashboard navigation">
            <div className="dashboard-sidebar-topbar">
                <div className="dashboard-sidebar-brand">
                    <SimdexLogo variant={collapsed ? 'mark' : 'full'} />
                </div>
                <button
                    type="button"
                    className="dashboard-sidebar-theme"
                    onClick={() => onThemeChange(theme === 'dark' ? 'light' : 'dark')}
                    aria-label={theme === 'dark' ? 'Use light mode' : 'Use night mode'}
                    title={theme === 'dark' ? 'Use light mode' : 'Use night mode'}
                >
                    {theme === 'dark'
                        ? <Sun aria-hidden="true" size={16} />
                        : <Moon aria-hidden="true" size={16} />}
                </button>
                <button
                    type="button"
                    className="dashboard-sidebar-collapse"
                    onClick={onToggleCollapsed}
                    aria-label={collapsed ? 'Enlarge navigation bar' : 'Reduce navigation bar'}
                    title={collapsed ? 'Enlarge navigation bar' : 'Reduce navigation bar'}
                >
                    {collapsed
                        ? <PanelLeftOpen aria-hidden="true" size={17} />
                        : <PanelLeftClose aria-hidden="true" size={17} />}
                </button>
                <button type="button" className="dashboard-sidebar-close" onClick={onClose} aria-label="Close menu">
                    <X aria-hidden="true" size={17} />
                </button>
            </div>

            <div className="dashboard-sidebar-profile">
                <span className="dashboard-sidebar-avatar">{initials || 'AD'}</span>
                <div>
                    <strong>{user.name}</strong>
                    <small>Student Data Administrator</small>
                </div>
            </div>

            <nav className="dashboard-sidebar-nav">
                <SidebarLink
                    icon={Users}
                    label="Student Data"
                    count={stats.total}
                    target="students-table"
                    active={activeItem === 'students-table'}
                    onNavigate={navigate}
                />
                <SidebarLink
                    icon={BarChart3}
                    label="Analytics"
                    target="student-analytics"
                    active={activeItem === 'student-analytics'}
                    onNavigate={navigate}
                />
                <SidebarLink
                    icon={Upload}
                    label="Import & Export"
                    target="student-import"
                    active={activeItem === 'student-import'}
                    onNavigate={navigate}
                />
            </nav>

            <div className="dashboard-sidebar-workspace">
                <div className="dashboard-sidebar-section-heading">
                    <span>Majors</span>
                    <GraduationCap aria-hidden="true" size={15} />
                </div>

                <div className="dashboard-sidebar-major-list">
                    {analysis.major_distribution.length === 0 ? (
                        <span className="dashboard-sidebar-empty">No major data</span>
                    ) : analysis.major_distribution.slice(0, 5).map((major) => (
                        <button
                            type="button"
                            key={major.major}
                            onClick={() => navigate('student-analytics')}
                            title={`${major.major}: ${major.count} students`}
                        >
                            <span><i />{major.major}</span>
                            <b>{major.count}</b>
                        </button>
                    ))}
                </div>
            </div>

            <div className="dashboard-sidebar-footer">
                <form method="POST" action={routes.logout}>
                    <input type="hidden" name="_token" value={csrf} />
                    <button type="submit" className="dashboard-sidebar-logout" title="Log out">
                        <LogOut aria-hidden="true" size={16} />
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </aside>
    );
}
