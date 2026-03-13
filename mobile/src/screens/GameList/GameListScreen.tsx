import React, {useEffect, useState} from 'react';
import {View, Text, StyleSheet, FlatList, ActivityIndicator, TouchableOpacity, Alert} from 'react-native';
import GameService from '../../services/GameService';
import {Game} from '../../types';

const GameListScreen = ({route, navigation}: any) => {
    const {eventId, eventNumber} = route.params;
    const [games, setGames] = useState<Game[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        navigation.setOptions({title: `Games - Event #${eventNumber}`});
        loadGames();
    }, [eventId]);

    const loadGames = async () => {
        try {
            const data = await GameService.getGamesByEvent(eventId);
            setGames(data);
        } catch (error) {
            console.error('Failed to load games', error);
            Alert.alert('Error', 'Could not load games for this event.');
        } finally {
            setLoading(false);
        }
    };

    const renderGameItem = ({item}: { item: Game }) => (
        <View style={styles.gameItem}>
            <View style={styles.gameHeader}>
                <Text style={styles.gameTime}>
                    {item.startingTime ? new Date(item.startingTime).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'TBD'}
                </Text>
                <View style={[styles.badge, item.isRecorded ? styles.recordedBadge : styles.pendingBadge]}>
                    <Text style={styles.badgeText}>{item.isRecorded ? 'Recorded' : 'Pending'}</Text>
                </View>
            </View>

            <View style={styles.playersContainer}>
                {item.players.length >= 2 ? (
                    <>
                        <Text style={styles.playerName}>{item.players[0].name}</Text>
                        <Text style={styles.vs}>vs</Text>
                        <Text style={styles.playerName}>{item.players[1].name}</Text>
                    </>
                ) : item.players.length === 1 ? (
                    <Text style={styles.playerName}>{item.players[0].name}</Text>
                ) : (
                    <Text style={styles.noPlayers}>No players assigned</Text>
                )}
            </View>

            <View style={styles.actionsContainer}>
                <TouchableOpacity
                    style={styles.actionButton}
                    onPress={() => navigation.navigate('ScoreEntry', {gameId: item.id})}
                >
                    <Text style={styles.actionButtonText}>{item.isRecorded ? 'View Scores' : 'Enter Scores'}</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[styles.actionButton, styles.subButton]}
                    onPress={() => navigation.navigate('Substitution', {gameId: item.id})}
                >
                    <Text style={styles.subButtonText}>Change Players</Text>
                </TouchableOpacity>
            </View>
        </View>
    );

    if (loading) {
        return <ActivityIndicator style={styles.centered} size="large"/>;
    }

    return (
        <View style={styles.container}>
            <FlatList
                data={games}
                renderItem={renderGameItem}
                keyExtractor={(item) => item.id.toString()}
                ListEmptyComponent={
                    <View style={styles.centered}>
                        <Text>No games found for this event.</Text>
                    </View>
                }
                contentContainerStyle={styles.listContent}
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#f5f5f5'},
    listContent: {padding: 15},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center', marginTop: 50},
    gameItem: {
        backgroundColor: '#fff',
        borderRadius: 10,
        padding: 15,
        marginBottom: 15,
        shadowColor: '#000',
        shadowOffset: {width: 0, height: 2},
        shadowOpacity: 0.1,
        shadowRadius: 4,
        elevation: 3,
    },
    gameHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 10,
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
        paddingBottom: 8,
    },
    gameTime: {fontSize: 16, fontWeight: 'bold', color: '#333'},
    badge: {paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4},
    recordedBadge: {backgroundColor: '#34C759'},
    pendingBadge: {backgroundColor: '#FF9500'},
    badgeText: {color: '#fff', fontSize: 10, fontWeight: 'bold', textTransform: 'uppercase'},
    playersContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 10,
    },
    playerName: {fontSize: 16, color: '#333', flex: 1, textAlign: 'center'},
    vs: {fontSize: 12, color: '#999', marginHorizontal: 10},
    noPlayers: {color: '#999', fontStyle: 'italic'},
    actionButton: {
        backgroundColor: '#f0f0f0',
        padding: 10,
        borderRadius: 6,
        alignItems: 'center',
        flex: 1,
    },
    actionButtonText: {color: '#007AFF', fontWeight: 'bold'},
    actionsContainer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginTop: 10,
    },
    subButton: {
        backgroundColor: '#f9f9f9',
        borderWidth: 1,
        borderColor: '#ddd',
        flex: 1,
        marginLeft: 10,
        marginTop: 0,
    },
    subButtonText: {color: '#666', fontSize: 14},
});

export default GameListScreen;
