@props(['class' => 'w-auto h-8'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <!-- Elysium Nexus Icon - Stylized P2P Nodes -->
    <svg viewBox="0 0 100 100" class="{{ $class }}" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#a855f7;stop-opacity:1" />
            </linearGradient>
            <filter id="logo-glow" x="-20%" y="-20%" width="140%" height="140%">
                <feGaussianBlur stdDeviation="2" result="blur" />
                <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
        </defs>
        
        <!-- Central Node -->
        <circle cx="50" cy="50" r="12" fill="url(#logo-gradient)" filter="url(#logo-glow)" />
        
        <!-- Orbital Paths -->
        <circle cx="50" cy="50" r="35" stroke="rgba(255,255,255,0.15)" stroke-width="1" stroke-dasharray="4 8" />
        
        <!-- Satellite Nodes (P2P Mesh Representation) -->
        <circle cx="50" cy="15" r="5" fill="#6366f1" />
        <circle cx="85" cy="50" r="5" fill="#a855f7" />
        <circle cx="50" cy="85" r="5" fill="#6366f1" />
        <circle cx="15" cy="50" r="5" fill="#a855f7" />
        
        <!-- Connection Lines -->
        <path d="M50 20 L50 38" stroke="url(#logo-gradient)" stroke-width="2" stroke-linecap="round" opacity="0.6" />
        <path d="M80 50 L62 50" stroke="url(#logo-gradient)" stroke-width="2" stroke-linecap="round" opacity="0.6" />
        <path d="M50 80 L50 62" stroke="url(#logo-gradient)" stroke-width="2" stroke-linecap="round" opacity="0.6" />
        <path d="M20 50 L38 50" stroke="url(#logo-gradient)" stroke-width="2" stroke-linecap="round" opacity="0.6" />
        
        <!-- Outer Glint -->
        <path d="M30 30 Q50 10 70 30" stroke="white" stroke-width="1.5" stroke-linecap="round" opacity="0.4" />
    </svg>
</div>
